<?php
namespace DreamFactory\Core\User\Services;

use DreamFactory\Core\Enums\ServiceRequestorTypes;
use DreamFactory\Core\Models\Service;
use DreamFactory\Core\User\Models\UserCustom;
use DreamFactory\Core\User\Resources\Custom;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Resources\BaseRestResource;
use DreamFactory\Core\User\Resources\Password;
use DreamFactory\Core\User\Resources\Profile;
use DreamFactory\Core\User\Resources\Register;
use DreamFactory\Core\User\Resources\Session;
use DreamFactory\Core\Utility\Session as SessionUtility;
use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Library\Utility\Inflector;

class User extends BaseRestService
{
    protected static $resources = [
        Password::RESOURCE_NAME => [
            'name'       => Password::RESOURCE_NAME,
            'class_name' => Password::class,
            'label'      => 'Password'
        ],
        Profile::RESOURCE_NAME  => [
            'name'       => Profile::RESOURCE_NAME,
            'class_name' => Profile::class,
            'label'      => 'Profile'
        ],
        Register::RESOURCE_NAME => [
            'name'       => Register::RESOURCE_NAME,
            'class_name' => Register::class,
            'label'      => 'Register'
        ],
        Session::RESOURCE_NAME  => [
            'name'       => Session::RESOURCE_NAME,
            'class_name' => Session::class,
            'label'      => 'Session'
        ],
        Custom::RESOURCE_NAME   => [
            'name'       => Custom::RESOURCE_NAME,
            'class_name' => Custom::class,
            'label'      => 'Custom'
        ]
    ];

    public function getResources($only_handlers = false)
    {
        return ($only_handlers) ? static::$resources : array_values(static::$resources);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessList()
    {
        $list = parent::getAccessList();
        $nameField = static::getResourceIdentifier();
        foreach ($this->getResources() as $resource)
        {
            $name = ArrayUtils::get($resource, $nameField);
            if (!empty($this->getPermissions())) {
                $list[] = $name . '/';
                $list[] = $name . '/*';
            }
        }

        return $list;
    }

    public static function getApiDocInfo(Service $service)
    {
        $base = parent::getApiDocInfo($service);

        $apis = [];
        $models = [];
        foreach (static::$resources as $resourceInfo) {
            $resourceClass = ArrayUtils::get($resourceInfo, 'class_name');

            if (!class_exists($resourceClass)) {
                throw new InternalServerErrorException('Service configuration class name lookup failed for resource ' .
                    $resourceClass);
            }

            $resourceName = ArrayUtils::get($resourceInfo, static::RESOURCE_IDENTIFIER);
            $access = SessionUtility::getServicePermissions($service->name, $resourceName, ServiceRequestorTypes::API);
            if (!empty($access)) {
                $results = $resourceClass::getApiDocInfo($service, $resourceInfo);
                if (isset($results, $results['paths'])) {
                    $apis = array_merge($apis, $results['paths']);
                }
                if (isset($results, $results['definitions'])) {
                    $models = array_merge($models, $results['definitions']);
                }
            }
        }

        $base['paths'] = array_merge($base['paths'], $apis);
        $base['definitions'] = array_merge($base['definitions'], $models);

        return $base;
    }
}