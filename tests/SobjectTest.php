<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class SobjectTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function testInsert()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(201, [], json_encode($this->createSuccessArray())),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $sobject = $salesforce->sobject()->insert('Account', $this->dataArray());

        $this->assertEquals('001D000000IqhSLIAZ', $sobject->id);
        $this->assertTrue($sobject->success);
        $this->assertEquals(201, (int) $sobject->http_status_code);
    }

    public function testGet()
    {
        $Id = '001D000000IqhSLIAZ';

        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], json_encode($this->dataArray([
                'Id' => $Id,
            ]))),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $sobject = $salesforce->sobject()->get($Id, 'Account');

        $this->assertEquals($Id, $sobject->sobject->Id);
        $this->assertEquals('Test Account 1', $sobject->sobject->Name);
        $this->assertTrue($sobject->success);
        $this->assertEquals(200, (int) $sobject->http_status_code);
    }

    public function testGetFailure()
    {
        $Id = '001D000000IqhSNIAZ';

        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(404, [], $this->errorReturn()),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $sobject = $salesforce->sobject()->get($Id, 'Account');

        $this->assertEquals('The requested resource does not exist', $sobject->error->message);
        $this->assertTrue(!$sobject->success);
        $this->assertEquals(404, (int) $sobject->http_status_code);
    }

    public function testUpdate()
    {
        $Id = '001D000000IqhSLIAZ';

        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(204, [], ''),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $sobject = $salesforce->sobject()->update($Id, 'Account', $this->dataArray());

        $this->assertTrue($sobject->success);
        $this->assertEquals('update', $sobject->operation);
        $this->assertEquals(204, (int) $sobject->http_status_code);
    }

    public function testDelete()
    {
        $Id = '001D000000IqhSLIAZ';

        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(204, [], ''),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $sobject = $salesforce->sobject()->delete($Id, 'Account');

        $this->assertTrue($sobject->success);
        $this->assertEquals('delete', $sobject->operation);
        $this->assertEquals(204, (int) $sobject->http_status_code);
    }

    public function testExternalGet()
    {
        $Id = '001D000000IqhSLIAZ';

        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], json_encode($this->dataArray([
                'Id' => $Id,
            ]))),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $sobject = $salesforce->sobject()->externalGet('External_Field__c', 'EXTERNAL_ID', 'Account');

        $this->assertEquals($Id, $sobject->sobject->Id);
        $this->assertEquals('Test Account 1', $sobject->sobject->Name);
        $this->assertTrue($sobject->success);
        $this->assertEquals(200, (int) $sobject->http_status_code);
    }

    public function testExternalUpsert()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(204, [], ''),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $sobject = $salesforce->sobject()->externalUpsert('External_Field__c', 'EXTERNAL_ID', 'Account', $this->dataArray());

        $this->assertTrue($sobject->success);
        $this->assertEquals('update', $sobject->operation);
        $this->assertEquals(204, (int) $sobject->http_status_code);
    }

    public function createSuccessArray($overrides = [])
    {
        return array_replace([
            'id'      => '001D000000IqhSLIAZ',
            'errors'  => [],
            'success' => true,
        ], $overrides);
    }

    public function dataArray($overrides = [])
    {
        return array_replace([
            'Name'        => 'Test Account 1',
            'description' => 'Created from Bulk API',
        ], $overrides);
    }

    public function errorReturn()
    {
        return '[{"message":"The requested resource does not exist","errorCode":"NOT_FOUND"}]';
    }
}
