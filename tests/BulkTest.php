<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class BulkTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function testBulkJobCreate()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(201, [], json_encode($this->jobArray())),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $job = $salesforce->bulk()->createJob('insert', 'Account');

        $this->assertEquals('750D00000004SkVIAU', $job->id);
    }

    public function testBulkJobDetails()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], json_encode($this->jobArray(['state' => 'Closed']))),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $jobId = '750D00000004SkVIAU';

        $job = $salesforce->bulk()->jobDetails($jobId);

        $this->assertEquals($jobId, $job->id);
        $this->assertEquals('Closed', $job->state);
    }

    public function testBulkJobClose()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], json_encode($this->jobArray(['state' => 'Closed']))),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $jobId = '750D00000004SkVIAU';

        $job = $salesforce->bulk()->closeJob($jobId);

        $this->assertEquals($jobId, $job->id);
        $this->assertEquals('Closed', $job->state);
    }

    public function testBulkBatchCreate()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], json_encode($this->batchArray())),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $jobId = '750D00000004SkVIAU';

        $batch = $salesforce->bulk()->addBatch($jobId, $this->dataArray());

        $this->assertEquals('750D00000004SkGIAU', $batch->id);
        $this->assertEquals($jobId, $batch->jobId);
    }

    public function testBulkBatchDetails()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], json_encode($this->batchArray())),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $jobId = '750D00000004SkVIAU';
        $batchId = '750D00000004SkGIAU';

        $batch = $salesforce->bulk()->batchDetails($jobId, $batchId);

        $this->assertEquals($batchId, $batch->id);
        $this->assertEquals('Completed', $batch->state);
    }

    public function testRunBatch()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], json_encode($this->jobArray())),
            new Response(200, [], json_encode($this->batchArray(['state' => 'Queued']))),
            new Response(200, [], json_encode($this->batchArray())),
            new Response(200, [], json_encode($this->dataResultArray())),
            new Response(200, [], json_encode($this->jobArray())),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $jobId = '750D00000004SkVIAU';
        $batchId = '750D00000004SkGIAU';
        $accountCreatedId = '001xx000003DHP0AAO';

        $operation = 'insert';
        $objectType = 'Account';
        $data = $this->dataArray();

        $job = $salesforce->bulk()->runBatch($operation, $objectType, $data);

        $this->assertEquals($jobId, $job->id);

        foreach ($job->batches as $batch) {
            $this->assertEquals($batchId, $batch->id);
            foreach ($batch->records as $record) {
                $this->assertEquals($accountCreatedId, $record['id']);
                $this->assertTrue($record['success']);
                break;
            }
        }
    }

    public function testRunQueryBatch()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], json_encode($this->jobArray())),
            new Response(200, [], json_encode($this->batchArray(['state' => 'Queued']))),
            new Response(200, [], json_encode($this->batchArray())),
            new Response(200, [], json_encode($this->dataQueryResultArray())),
            new Response(200, [], json_encode($this->dataQueryDataResultArray())),
            new Response(200, [], json_encode($this->jobArray())),
        ]);

        $handler = HandlerStack::create($mock);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'handler'                        => $handler,
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
        ]);

        $jobId = '750D00000004SkVIAU';
        $batchId = '750D00000004SkGIAU';
        $firstAccountId = '0014000001iM8r3AAC';

        $operation = 'query';
        $objectType = 'Account';
        $data = $this->dataArray();

        $job = $salesforce->bulk()->runBatch($operation, $objectType, $data);

        $this->assertEquals($jobId, $job->id);

        foreach ($job->batches as $batch) {
            $this->assertEquals($batchId, $batch->id);
            foreach ($batch->records as $record) {
                $this->assertEquals($firstAccountId, $record['Id']);
                break;
            }
        }
    }

    public function testBulkBinaryJobCreateWithMiddleware()
    {
        copy(realpath('./tests/bulk_zip_files').'/zipped_dir.zip', realpath('./build').'/test.zip');

        \Frankkessler\Salesforce\SalesforceConfig::reset();
        GuzzleServer::flush();
        GuzzleServer::start();

        GuzzleServer::enqueue([
            new Response(200, [], json_encode($this->jobArray())),
            new Response(200, [], $this->batchArrayBinary('Queued')),
            new Response(200, [], $this->batchArrayBinary()),
            new Response(200, [], $this->dataResultCsv()),
            new Response(200, [], json_encode($this->jobArray())),
        ]);

        $salesforce = new \Frankkessler\Salesforce\Salesforce([
            'salesforce.oauth.access_token'  => 'TEST',
            'salesforce.oauth.refresh_token' => 'TEST',
            'auth'                           => 'bulk',
            'base_uri'                       => GuzzleServer::$url,
            'bulk_base_uri'                  => GuzzleServer::$url,
            'token_url'                      => GuzzleServer::$url,
        ]);

        $jobId = '750D00000004SkVIAU';
        $batchId = '751540000010UC6AAM';
        $attachmentCreatedId = '001xx000003DHP1AAO';

        $operation = 'insert';
        $objectType = 'Attachment';

        $binaryBatch = new \Frankkessler\Salesforce\DataObjects\BinaryBatch([
            'batchZip' => realpath('./build').'/test.zip',
        ]);

        $attachmentArray = json_decode($this->requestDotTxtContents());

        foreach ($attachmentArray as $attachArray) {
            $binaryBatch->attachments[] = new \Frankkessler\Salesforce\DataObjects\Attachment($attachArray);
        }

        $job = $salesforce->bulk()->runBinaryUploadBatch($operation, $objectType, [$binaryBatch]);

        $this->assertEquals($jobId, $job->id);

        foreach ($job->batches as $batch) {
            $this->assertEquals($batchId, $batch->id);
            foreach ($batch->records as $record) {
                $this->assertEquals($attachmentCreatedId, $record['id']);
                $this->assertEquals(true,$record['success']);
                break;
            }
        }

        $i = 1;
        foreach (GuzzleServer::received() as $response) {
            switch ($i) {
                case 1:
                case 3:
                case 4:
                case 5:
                    break;
                case 2:
                    file_put_contents(realpath('./build').'/test_download.zip', $response->getBody());
                    $this->assertEquals(md5_file(realpath('./build').'/test.zip'), md5_file(realpath('./build').'/test_download.zip'));
                    $this->assertEquals('THIS IS TEST DATA', $this->getTestText());
                    break;
                default:
                    //this should never be called.  If it is something went wrong.
                    $this->assertEquals(0, $response->getStatusCode());
            }
            $i++;
        }

        //make sure 5 requests occurred or something went wrong.
        $this->assertEquals(5, $i - 1);

        GuzzleServer::flush();
    }

    public function getTestText()
    {
        $zip = new ZipArchive();
        if ($zip->open(realpath('./build/test_download.zip'), \ZIPARCHIVE::CREATE) === true) {
            $zip->extractTo(realpath('./build/'));
            $zip->close();
        } else {
            throw(new \Exception('Batch zip cannot be opened'));
        }

        return file_get_contents(realpath('./build/').'/test.txt');
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

    public function batchArray($overrides = [])
    {
        return array_replace([
            'apexProcessingTime'      => 0,
            'apiActiveProcessingTime' => 0,
            'createdDate'             => '2015-12-15T20:45:25.000+0000',
            'id'                      => '750D00000004SkGIAU',
            'jobId'                   => '750D00000004SkVIAU',
            'numberRecordsFailed'     => 0,
            'numberRecordsProcessed'  => 0,
            'state'                   => 'Completed',
            'systemModstamp'          => '2015-12-15T20:45:25.000+0000',
            'totalProcessingTime'     => 0,
        ], $overrides);
    }

    public function batchArrayBinary($state='Completed')
    {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?><batchInfo xmlns=\"http://www.force.com/2009/06/asyncapi/dataload\"><id>751540000010UC6AAM</id><jobId>750540000010wizAAA</jobId><state>".$state."</state><createdDate>2016-11-01T19:14:57.000Z</createdDate><systemModstamp>2016-11-01T19:14:58.000Z</systemModstamp><numberRecordsProcessed>1</numberRecordsProcessed><numberRecordsFailed>0</numberRecordsFailed><totalProcessingTime>342</totalProcessingTime><apiActiveProcessingTime>205</apiActiveProcessingTime><apexProcessingTime>0</apexProcessingTime></batchInfo>";
    }

    public function dataArray()
    {
        return [
            [
                'Name'        => 'Test Account 1',
                'description' => 'Created from Bulk API',
            ],
            [
                'Name'        => 'Test Account 2',
                'description' => 'Created from Bulk API',
            ],
        ];
    }

    public function dataResultArray()
    {
        return [
            [
                'success' => true,
                'created' => true,
                'id'      => '001xx000003DHP0AAO',
                'errors'  => [],
            ],
            [
                'success' => true,
                'created' => true,
                'id'      => '001xx000003DHP1AAO',
                'errors'  => [],
            ],
        ];
    }

    public function dataResultCsv()
    {
        return 'Success,Created,Id,Errors
true,true,001xx000003DHP1AAO,';
    }

    public function dataQueryResultArray()
    {
        return ['742400000022G6b'];
    }

    public function dataQueryDataResultArray()
    {
        return json_decode(
            '[
                {
                  "attributes" : {
                    "type" : "Account",
                    "url" : "/services/data/v36.0/sobjects/Account/0014000001iM8r3AAC"
                  },
                  "Id" : "0014000001iM8r3AAC",
                  "Name" : "Greatest Bank"
                }, {
                  "attributes" : {
                    "type" : "Account",
                    "url" : "/services/data/v36.0/sobjects/Account/0014000001iM8S5AAK"
                  },
                  "Id" : "0014000001iM8S5AAK",
                  "Name" : "Regions Insurance"
                }
            ]',
            true
        );
    }

    public function requestDotTxtContents()
    {
        //first account is Greatest bank.  Second is Regions Insurance
        return '[
            {
                "Name" : "test.txt",
                "ParentId" : "0014000001iM8S5AAK",
                "Body" : "#test.txt"
            }
        ]';
    }
}
