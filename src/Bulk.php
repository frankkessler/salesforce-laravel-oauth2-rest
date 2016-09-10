<?php

namespace Frankkessler\Salesforce;

use Frankkessler\Salesforce\Client\BulkClient;
use Frankkessler\Salesforce\Responses\BulkBatchResponse;
use Frankkessler\Salesforce\Responses\BulkJobResponse;

class Bulk extends Salesforce
{
    public function __construct($config=[])
    {
        $base_uri = 'https://'.SalesforceConfig::get('salesforce.api.domain').SalesforceConfig::get('salesforce.api.base_uri');

        $client_config = [
            'base_uri' => $base_uri,
            'auth' => 'bulk',
        ];

        if(isset($config['handler'])){
            $client_config['handler'] = $config['handler'];
        }

        $this->oauth2Client = new BulkClient($client_config);
        parent::__construct($config);
    }

    public function runBatch($operation, $objectType, $data, $batchSize=2000, $batchTimeout=600, $contentType='json', $pollIntervalSeconds=5)
    {
        $batches = [];

        $job = $this->createJob($operation, $objectType, $contentType);

        $totalNumberOfBatches = ceil(count($data)/$batchSize);

        for($i=1;$i<=$totalNumberOfBatches;$i++) {
            $batches[] = $this->addBatch($job->id, $data);
        }

        $time = time();
        $timeout = $time + $batchTimeout;

        $batches_finished = [];

        while(count($batches_finished) < count($batches) && $time < $timeout){
            $last_time_start = time();
            foreach($batches as &$batch) {
                //skip processing if batch is already done processing
                if(in_array($batch->id, $batches_finished)){
                    continue;
                }

                $batch = $this->batchDetails($job->id, $batch->id);
                if (in_array($batch->state, ['Completed','Failed','Not Processed'])) {
                    $batch->records = $this->batchResult($job->id, $batch->id);
                    $batches_finished[] = $batch->id;
                }
            }

            //if we aren't complete yet, look to sleep for a few seconds so we don't poll constantly
            if(count($batches_finished) < count($batches)) {
                //If the polling for all batches hasn't taken at least the amount of time set for the polling interval, wait the additional time and then continue processing.
                $wait_time = time() - $last_time_start;
                if ($wait_time < $pollIntervalSeconds) {
                    sleep($pollIntervalSeconds - $wait_time);
                }
            }
            $time = time();
        }

        $job = $this->jobDetails($job->id);
        $job->batches = $batches;

        return $job;
    }

    /**
     * @param string $operation
     * @param string $objectType
     * @param string $contentType
     * @return BulkJobResponse
     */

    public function createJob($operation, $objectType, $contentType='json')
    {
        $url = 'services/async/'.SalesforceConfig::get('salesforce.api.version').'/job';
        $json_array = [
            'operation' => $operation,
            'object' => $objectType,
            'contentType' => $contentType,
        ];

        $result = $this->call_api('post',$url, [
            'body' => json_encode($json_array),
            'headers' => [
                'Content-type' => 'application/json',
            ]
        ]);

        if($result && isset($result['id']) && $result['id']){
            return new BulkJobResponse($result);
        }
        return new BulkJobResponse();
    }

    public function jobDetails($jobId)
    {
        $url = 'services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId;

        $result = $this->call_api('get',$url);

        if($result && isset($result['id']) && $result['id']){
            return new BulkJobResponse($result);
        }
        else{
            //throw exception
        }

        return new BulkJobResponse();
    }

    /**
     * @param $jobId
     * @return BulkJobResponse
     */
    public function closeJob($jobId)
    {
        $url = 'services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId;

        $json_array = [
            'state' => 'Closed',
        ];

        $result = $this->call_api('post',$url, [
            'body' => json_encode($json_array),
            'headers' => [
                'Content-type' => 'application/json',
            ]
        ]);

        if($result && isset($result['id']) && $result['id']){
            return new BulkJobResponse($result);
        }

        return new BulkJobResponse();
    }

    /**
     * @param $jobId
     * @param $data
     * @return BulkBatchResponse
     */
    public function addBatch($jobId, $data)
    {
        $url = 'services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch';

        $result = $this->call_api('post',$url, [
            'body' => json_encode($data),
            'headers' => [
                'Content-type' => 'application/json',
            ]
        ]);

        if($result){
            return new BulkBatchResponse($result);
        }

        return new BulkBatchResponse();
    }

    /**
     * @param $jobId
     * @param $batchId
     * @return BulkBatchResponse
     */

    public function batchDetails($jobId, $batchId)
    {
        $url = 'services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch/'.$batchId;

        $result = $this->call_api('get',$url);

        if($result && isset($result['id']) && $result['id']){
            return new BulkBatchResponse($result);
        }else{
            //throw exception
        }
        return new BulkBatchResponse();
    }

    /**
     * @param $jobId
     * @param $batchId
     * @return array
     */

    public function batchResult($jobId, $batchId)
    {
        $url = 'services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch/'.$batchId.'/result';

        $result = $this->call_api('get',$url);
var_dump($result);
        if($result && is_array($result)){
            return $result;
        }
        return [];
    }
}