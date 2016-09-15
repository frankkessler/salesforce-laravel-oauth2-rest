<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class QueryTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
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

    public function testQueryAll()
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

        $result = $salesforce->query()->queryAll('SELECT Id, Name FROM Account LIMIT 2');

        $this->assertTrue($result->success);
        $this->assertEquals(2, count($result->records));
        $this->assertEquals(2, $result->totalSize);

        foreach ($result->records as $record) {
            $this->assertEquals('Test Account', $record['Name']);
            $this->assertEquals('001D000000IRFmaIAH', $record['Id']);
            break;
        }
    }

    public function testQueryFollowNext()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], $this->queryFollowNextFirstRequestDataResult()),
            new Response(200, [], $this->queryFollowNextSecondRequestDataResult()),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $result = $salesforce->query()->queryFollowNext('SELECT Id, Name FROM Account LIMIT 4');

        $this->assertTrue($result->success);
        $this->assertEquals(4, count($result->records));
        $this->assertEquals(4, $result->totalSize);

        $i = 1;
        foreach ($result->records as $record) {
            switch ($i) {
                case 1:
                    $this->assertEquals('Test Account', $record['Name']);
                    $this->assertEquals('001D000000IRFmaIAH', $record['Id']);
                    break;
                case 2:
                    $this->assertEquals('Test Account 2', $record['Name']);
                    $this->assertEquals('001D000000IomazIAB', $record['Id']);
                    break;
                case 3:
                    $this->assertEquals('Test Account 3', $record['Name']);
                    $this->assertEquals('001D000000IRFmaIAG', $record['Id']);
                    break;

                case 4:
                    $this->assertEquals('Test Account 4', $record['Name']);
                    $this->assertEquals('001D000000IomazIAC', $record['Id']);
                    break;
            }

            $i++;
        }
    }

    public function testQueryAllFollowNext()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], $this->queryFollowNextFirstRequestDataResult()),
            new Response(200, [], $this->queryFollowNextSecondRequestDataResult()),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $result = $salesforce->query()->queryAllFollowNext('SELECT Id, Name FROM Account LIMIT 4');

        $this->assertTrue($result->success);
        $this->assertEquals(4, count($result->records));
        $this->assertEquals(4, $result->totalSize);

        $i = 1;
        foreach ($result->records as $record) {
            switch ($i) {
                case 1:
                    $this->assertEquals('Test Account', $record['Name']);
                    $this->assertEquals('001D000000IRFmaIAH', $record['Id']);
                    break;
                case 2:
                    $this->assertEquals('Test Account 2', $record['Name']);
                    $this->assertEquals('001D000000IomazIAB', $record['Id']);
                    break;
                case 3:
                    $this->assertEquals('Test Account 3', $record['Name']);
                    $this->assertEquals('001D000000IRFmaIAG', $record['Id']);
                    break;

                case 4:
                    $this->assertEquals('Test Account 4', $record['Name']);
                    $this->assertEquals('001D000000IomazIAC', $record['Id']);
                    break;
            }

            $i++;
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

    public function queryFollowNextFirstRequestDataResult()
    {
        return
            '{
                "done" : true,
                "totalSize" : 4,
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
                ],
                "nextRecordsUrl": "https://na2.salesforce.com/00XAUNI789789UA"
            }';
    }

    public function queryFollowNextSecondRequestDataResult()
    {
        return
            '{
                "done" : true,
                "totalSize" : 4,
                "records" :
                [
                    {
                        "attributes" :
                        {
                            "type" : "Account",
                            "url" : "/services/data/v20.0/sobjects/Account/001D000000IRFmaIAG"
                        },
                        "Id" : "001D000000IRFmaIAG",
                        "Name" : "Test Account 3"
                    },
                    {
                        "attributes" :
                        {
                            "type" : "Account",
                            "url" : "/services/data/v20.0/sobjects/Account/001D000000IomazIAC"
                        },
                        "Id" : "001D000000IomazIAC",
                        "Name" : "Test Account 4"
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
