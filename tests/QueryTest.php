<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class QueryTest extends \Mockery\Adapter\PHPUnit\MockeryTestCase
{
    public function testQuery()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], $this->queryDataResult()),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $result = $salesforce->query()->query('SELECT Id, Name FROM Account LIMIT 2');

        $this->assertTrue($result->success);
        $this->assertEquals(2, count($result->records));
        $this->assertEquals(2, $result->totalSize);

        foreach ($result->records as $record) {
            $this->assertEquals('Test Account', $record['Name']);
            $this->assertEquals('001D000000IRFmaIAH', $record['Id']);
            break;
        }
    }

    public function testSearch()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], $this->searchDataResult()),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $result = $salesforce->query()->search('FIND {TEST ACCOUNT} IN ALL FIELDS RETURNING Account(Id, Name)');

        $this->assertTrue($result->success);
        $this->assertEquals(2, count($result->records));

        foreach ($result->records as $record) {
            $this->assertEquals('Test Account', $record['Name']);
            $this->assertEquals('001D000000IqhSLIAZ', $record['Id']);
            break;
        }
    }

    public function queryDataResult()
    {
        return
            '{
                "done" : true,
                "totalSize" : 2,
                "records" :
                [
                    {
                        "attributes" :
                        {
                            "type" : "Account",
                            "url" : "/services/data/v20.0/sobjects/Account/001D000000IRFmaIAH"
                        },
                        "Id" : "001D000000IRFmaIAH",
                        "Name" : "Test Account"
                    },
                    {
                        "attributes" :
                        {
                            "type" : "Account",
                            "url" : "/services/data/v20.0/sobjects/Account/001D000000IomazIAB"
                        },
                        "Id" : "001D000000IomazIAB",
                        "Name" : "Test Account 2"
                    }
                ]
            }';
    }

    public function searchDataResult()
    {
        return
            '[
                {
                    "attributes" : {
                        "type" : "Account",
                        "url" : "/services/data/v35.0/sobjects/Account/001D000000IqhSLIAZ"
                    },
                    "Id" : "001D000000IqhSLIAZ",
                    "Name" : "Test Account"
                },
                {
                    "attributes" : {
                        "type" : "Account",
                        "url" : "/services/data/v35.0/sobjects/Account/001D000000IomazIAB"
                    },
                    "Id" : "001D000000IomazIAB",
                    "Name" : "Test Account2"
                }
            ]';
    }
}
