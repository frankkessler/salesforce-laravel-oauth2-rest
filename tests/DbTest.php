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

        $data = [
            'refresh_token' => $refreshTokenString,
            'expires'       => null,
            'instance_url'  => 'https://na1.salesforce.com',
        ];

        $accessToken = new Frankkessler\Guzzle\Oauth2\AccessToken($accessTokenString, 'bearer', $data);

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
        $options = ['token_handler' => $handler];
        $result = $auth->processAuthenticationCode($code, $options);

        $this->assertEquals('Token record set successfully', $result);

        $repository = new \Frankkessler\Salesforce\Repositories\Eloquent\TokenEloquentRepository();

        $tokenRecord = $repository->getTokenRecord();

        $this->assertEquals('AUTH_TEST_TOKEN', $tokenRecord->access_token);
        $this->assertEquals('AUTH_TEST_REFRESH_TOKEN', $tokenRecord->refresh_token);
    }

    public function testBulkJobCreateWithMiddleware()
    {
        \Frankkessler\Salesforce\SalesforceConfig::reset();
        GuzzleServer::flush();
        GuzzleServer::start();

        GuzzleServer::enqueue([
            new Response(401, [], $this->returnInvalidGrant()),
            new Response(200, [], $this->returnRefreshTokenResponse()),
            new Response(201, [], json_encode($this->jobArray())),
        ]);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
            'auth'                           => 'bulk',
            'base_uri'                       => GuzzleServer::$url,
            'bulk_base_uri'                  => GuzzleServer::$url,
            'token_url'                      => GuzzleServer::$url,
        ]);

        $job = $salesforce->bulk()->createJob('insert', 'Account');

        $i = 1;
        foreach (GuzzleServer::received() as $response) {
            $request_body = (string) $response->getBody();
            switch ($i) {
                case 1:
                case 3:
                    $data = json_decode($request_body, true);
                    $this->assertEquals('insert', $data['operation']);
                    $this->assertEquals('Account', $data['object']);
                    break;
                case 2:
                    $this->assertTrue((bool) strpos($request_body, 'refresh_token=TEST'));
                    break;
                default:
                    //this should never be called.  If it is something went wrong.
                    $this->assertEquals(0, $response->getStatusCode());
            }
            $i++;
        }

        $this->assertEquals('750D00000004SkVIAU', $job->id);

        GuzzleServer::flush();
    }

    public function returnInvalidGrant()
    {
        return '[{"message":"Authentication failure","errorCode":"invalid_grant"}]';
    }

    public function returnAuthorizationCodeAccessTokenResponse()
    {
        return
            '{
            "access_token": "AUTH_TEST_TOKEN",
            "refresh_token": "AUTH_TEST_REFRESH_TOKEN",
            "instance_url": "https://na1.salesforce.com",
            "token_type": "bearer"
        }';
    }

    public function returnRefreshTokenResponse()
    {
        return
            '{
            "id":"https://login.salesforce.com/id/00Dx0000000BV7z/005x00000012Q9P",
            "issued_at":"1278448384422",
            "instance_url":"https://na1.salesforce.com/",
            "signature":"SSSbLO/gBhmmyNUvN18ODBDFYHzakxOMgqYtu+hDPsc=",
            "access_token":"00Dx0000000BV7z!AR8AQP0jITN80ESEsj5EbaZTFG0RNBaT1cyWk7TrqoDjoNIWQ2ME_sTZzBjfmOE6zMHq6y8PIW4eWze9JksNEkWUl.Cju7m4"
        }';
    }

    public function jobArray($overrides = [])
    {
        return array_replace([
            'apexProcessingTime'      => 0,
            'apiActiveProcessingTime' => 0,
            'apiVersion'              => 36.0,
            'concurrencyMode'         => 'Parallel',
            'contentType'             => 'JSON',
            'createdById'             => '005D0000001b0fFIAQ',
            'createdDate'             => '2015-12-15T20:45:25.000+0000',
            'id'                      => '750D00000004SkVIAU',
            'numberBatchesCompleted'  => 0,
            'numberBatchesFailed'     => 0,
            'numberBatchesInProgress' => 0,
            'numberBatchesQueued'     => 0,
            'numberBatchesTotal'      => 0,
            'numberRecordsFailed'     => 0,
            'numberRecordsProcessed'  => 0,
            'numberRetries'           => 0,
            'object'                  => 'Account',
            'operation'               => 'insert',
            'state'                   => 'Open',
            'systemModstamp'          => '2015-12-15T20:45:25.000+0000',
            'totalProcessingTime'     => 0,
        ], $overrides);
    }
}
