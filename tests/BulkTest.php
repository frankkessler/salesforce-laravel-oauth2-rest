<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class BulkTest extends \Mockery\Adapter\PHPUnit\MockeryTestCase
{
    public function testBulkJobCreate()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], json_encode($this->jobArray())),
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
}
