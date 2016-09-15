<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class CustomTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function testCustomGet()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], json_encode($this->customArray())),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $custom = $salesforce->custom()->get('last_patient');

        $this->assertEquals('001D000000IqhSLIAZ', $custom['Id']);
    }

    public function testCustomPost()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], json_encode($this->customArray())),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $account_id = '001D000000IqhSLIAZ';

        $custom = $salesforce->custom()->post('account_id', ['Id' => $account_id]);

        $this->assertEquals($account_id, $custom['Id']);
    }

    public function customArray($overrides = [])
    {
        return array_replace([
            'Id'      => '001D000000IqhSLIAZ',
        ], $overrides);
    }
}
