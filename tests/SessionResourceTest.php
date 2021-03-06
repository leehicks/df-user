<?php
use DreamFactory\Core\Enums\Verbs;
use DreamFactory\Core\Enums\ApiOptions;
use DreamFactory\Core\Utility\Session;
use Illuminate\Support\Arr;

class SessionResourceTest extends \DreamFactory\Core\Testing\TestCase
{
    const RESOURCE = 'session';

    protected $serviceId = 'user';

    protected $user1 = [
        'name'              => 'John Doe',
        'first_name'        => 'John',
        'last_name'         => 'Doe',
        'email'             => 'jdoe@dreamfactory.com',
        'password'          => 'test1234',
        'security_question' => 'Make of your first car?',
        'security_answer'   => 'mazda',
        'is_active'         => true
    ];

    protected $user2 = [
        'name'                   => 'Jane Doe',
        'first_name'             => 'Jane',
        'last_name'              => 'Doe',
        'email'                  => 'jadoe@dreamfactory.com',
        'password'               => 'test1234',
        'is_active'              => true,
        'lookup_by_user_id' => [
            [
                'name'    => 'test',
                'value'   => '1234',
                'private' => false
            ],
            [
                'name'    => 'test2',
                'value'   => '5678',
                'private' => true
            ]
        ]
    ];

    protected $user3 = [
        'name'                   => 'Dan Doe',
        'first_name'             => 'Dan',
        'last_name'              => 'Doe',
        'email'                  => 'ddoe@dreamfactory.com',
        'password'               => 'test1234',
        'is_active'              => true,
        'lookup_by_user_id' => [
            [
                'name'    => 'test',
                'value'   => '1234',
                'private' => false
            ],
            [
                'name'    => 'test2',
                'value'   => '5678',
                'private' => true
            ],
            [
                'name'    => 'test3',
                'value'   => '56789',
                'private' => true
            ]
        ]
    ];

    public function tearDown()
    {
        $this->deleteUser(1);
        $this->deleteUser(2);
        $this->deleteUser(3);

        parent::tearDown();
    }

    /************************************************
     * Session sub-resource test
     ************************************************/

    public function testGET()
    {
        $rs = $this->makeRequest(Verbs::GET, null, ['as_list' => true]);
        $content = $rs->getContent();

        $expected = [
            static::$wrapper => [
                'password',
                'profile',
                'register',
                'session',
                'custom'
            ]
        ];
        $this->assertEquals($expected, $content);
    }

    public function testSessionNotFound()
    {
        $rs = $this->makeRequest(Verbs::GET, static::RESOURCE);
        $this->assertEquals(401, $rs->getStatusCode());
    }

    public function testUnauthorizedSessionRequest()
    {
        $user = $this->createUser(1);

        Session::authenticate(['email' => $user['email'], 'password' => $this->user1['password']]);

        //Using a new instance here. Prev instance is set for user resource.
        $this->service = ServiceManager::getService('system');
        $rs = $this->makeRequest(Verbs::GET, 'admin/session');
        $this->assertEquals(401, $rs->getStatusCode());
    }

    public function testLogin()
    {
        Session::put('role.name', 'test');
        Session::put('role.id', 1);

        $user = $this->createUser(1);

        $payload = ['email' => $user['email'], 'password' => $this->user1['password']];

        $rs = $this->makeRequest(Verbs::POST, static::RESOURCE, [], $payload);
        $content = $rs->getContent();

        $this->assertEquals($user['first_name'], $content['first_name']);
        $this->assertTrue(!empty($content['session_id']));
    }

    public function testSessionBadPatchRequest()
    {
        $user = $this->createUser(1);
        $payload = ['name' => 'foo'];

        $rs = $this->makeRequest(Verbs::PATCH, static::RESOURCE . '/' . $user['id'], [], $payload);
        $this->assertEquals(400, $rs->getStatusCode());
    }

    public function testLogout()
    {
        Session::put('role.name', 'test');
        Session::put('role.id', 1);

        $user = $this->createUser(1);
        $payload = ['email' => $user['email'], 'password' => $this->user1['password']];
        $rs = $this->makeRequest(Verbs::POST, static::RESOURCE, [], $payload);
        $content = $rs->getContent();

        $this->assertTrue(!empty($content['session_id']));

        $rs = $this->makeRequest(Verbs::DELETE, static::RESOURCE);
        $content = $rs->getContent();

        $this->assertTrue($content['success']);

        $rs = $this->makeRequest(Verbs::GET, static::RESOURCE);
        $this->assertEquals(401, $rs->getStatusCode());
    }

    /************************************************
     * Helper methods
     ************************************************/

    protected function createUser($num)
    {
        $user = $this->{'user' . $num};
        $payload = json_encode([$user], JSON_UNESCAPED_SLASHES);

        $this->service = ServiceManager::getService('system');
        $rs =
            $this->makeRequest(Verbs::POST, 'user',
                [ApiOptions::FIELDS => '*', ApiOptions::RELATED => 'lookup_by_user_id'], $payload);
        $this->service = ServiceManager::getService($this->serviceId);

        $data = $rs->getContent();

        return Arr::get($data, static::$wrapper . '.0');
    }

    protected function deleteUser($num)
    {
        $user = $this->{'user' . $num};
        $email = Arr::get($user, 'email');
        \DreamFactory\Core\Models\User::whereEmail($email)->delete();
    }
}