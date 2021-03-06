<?php

namespace DreamFactory\Core\User;

use DreamFactory\Core\System\Components\SystemResourceManager;
use DreamFactory\Core\System\Components\SystemResourceType;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Models\SystemTableModelMapper;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use DreamFactory\Core\User\Models\UserConfig;
use DreamFactory\Core\User\Models\UserCustom;
use DreamFactory\Core\User\Services\User;
use DreamFactory\Core\User\Resources\System\User as UserResource;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df) {
            $df->addType(
                new ServiceType([
                    'name'              => 'user',
                    'label'             => 'User service',
                    'description'       => 'User service to allow user management.',
                    'group'             => ServiceTypeGroups::USER,
                    'singleton'         => true,
                    'config_handler'    => UserConfig::class,
                    'factory'           => function ($config) {
                        return new User($config);
                    },
                    'access_exceptions' => [
                        [
                            'verb_mask' => 31, //Allow all verbs
                            'resource'  => 'session',
                        ],
                        [
                            'verb_mask' => 2, //Allow POST only
                            'resource'  => 'password',
                        ],
                        [
                            'verb_mask' => 2, //Allow POST only
                            'resource'  => 'register',
                        ],
                        [
                            'verb_mask' => 15,
                            'resource'  => 'profile',
                        ],
                    ],
                ])
            );
        });

        // Add our service types.
        $this->app->resolving('df.system.resource', function (SystemResourceManager $df) {
            $df->addType(
                new SystemResourceType([
                    'name'        => 'user',
                    'label'       => 'User Management',
                    'description' => 'Allows user management capability.',
                    'class_name'  => UserResource::class,
                    'singleton'   => false,
                    'read_only'   => false
                ])
            );
        });

        // Add our table model mapping
        $this->app->resolving('df.system.table_model_map', function (SystemTableModelMapper $df) {
            $df->addMapping('user_custom', UserCustom::class);
        });
    }

    public function boot()
    {
        // add migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
