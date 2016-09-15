<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Capsule\Manager as Capsule;

include __DIR__.'/../migrations/salesforce.php';

class DbTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function setUp()
    {
        parent::setUp();

        $capsule = new Capsule();

        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $capsule->setAsGlobal();

        $capsule->bootEloquent();

        $tokenMigration = new CreateSalesforceTokensTable();
        $tokenMigration->up();
    }

    public function testEloquentSetRepository()
    {
        $user_id = 1;

        $accessTokenString = 'TEST_TOKEN';
        $refreshTokenString = 'TEST_REFRESH_TOKEN';
        $expires = 1473913598;

        $data = [
            'refresh_token' => $refreshTokenString,
            'expires'       => $expires,
            'instance_url'  => 'https://na1.salesforce.com',
        ];

        $accessToken = new CommerceGuys\Guzzle\Oauth2\AccessToken($accessTokenString, 'bearer', $data);

        $repository = new \Frankkessler\Salesforce\Repositories\Eloquent\TokenEloquentRepository();
        $repository->setTokenRecord($accessToken, $user_id);

        $tokenRecord = $repository->getTokenRecord($user_id);

        $this->assertEquals($accessTokenString, $tokenRecord->access_token);
        $this->assertEquals($refreshTokenString, $tokenRecord->refresh_token);
        $this->assertEquals($data['instance_url'], $tokenRecord->instance_base_url);
        $this->assertEquals($user_id, $tokenRecord->user_id);


        $newAccessTokenString = 'TEST_TOKEN_NEW';
        $newRefreshTokenString = 'TEST_REFRESH_TOKEN_NEW';
        $repository->setAccessToken($newAccessTokenString, $user_id);
        $repository->setRefreshToken($newRefreshTokenString, $user_id);

        $tokenRecord = $repository->getTokenRecord($user_id);

        $this->assertEquals($newAccessTokenString, $tokenRecord->access_token);
        $this->assertEquals($newRefreshTokenString, $tokenRecord->refresh_token);
    }

    public function testAuthorizationCodeFlow()
    {
        $code = 'AUTHORIZATION_CODE';

        \Frankkessler\Salesforce\SalesforceConfig::set('salesforce.oauth.consumer_token', 'TEST_CLIENT_ID');
        \Frankkessler\Salesforce\SalesforceConfig::set('salesforce.oauth.consumer_secret', 'TEST_CLIENT_SECRET');

        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], $this->returnAuthorizationCodeAccessTokenResponse()),
        ]);

        $handler = HandlerStack::create($mock);

        $auth = new \Frankkessler\Salesforce\Authentication();
        $options = ['handler' => $handler];
        $result = $auth->processAuthenticationCode($code, $options);

        $this->assertEquals('Token record set successfully', $result);

        $repository = new \Frankkessler\Salesforce\Repositories\Eloquent\TokenEloquentRepository();

        $tokenRecord = $repository->getTokenRecord();

        $this->assertEquals('AUTH_TEST_TOKEN', $tokenRecord->access_token);
        $this->assertEquals('AUTH_TEST_REFRESH_TOKEN', $tokenRecord->refresh_token);
    }

    public function returnAuthorizationCodeAccessTokenResponse()
    {
        return
        '{
            "access_token": "AUTH_TEST_TOKEN",
            "refresh_token": "AUTH_TEST_REFRESH_TOKEN",
            "instance_url": "https://na1.salesforce.com",
            "expires": 1473913598,
            "token_type": "bearer"
        }';
    }
}
