<?php

namespace Frankkessler\Salesforce;

use Frankkessler\Salesforce\Client\BulkClient;
use Frankkessler\Salesforce\Responses\Bulk\BulkBatchResponse;
use Frankkessler\Salesforce\Responses\Bulk\BulkBatchResultResponse;
use Frankkessler\Salesforce\Responses\Bulk\BulkJobResponse;

class Bulk extends Salesforce
{
    public function __construct($config = [])
    {
        $base_uri = 'https://'.SalesforceConfig::get('salesforce.api.domain');

        $client_config = [
            'base_uri' => $base_uri,
            'auth'     => 'bulk',
        ];

        if (isset($config['handler'])) {
            $client_config['handler'] = $config['handler'];
        }

        $this->oauth2Client = new BulkClient($client_config);
        parent::__construct($config);
    }

    public function runBatch($operation, $objectType, $data, $options = [])
    {
        $batches = [];

        $defaults = [
            'externalIdFieldName' => null,
            'batchSize'           => 2000,
            'batchTimeout'        => 600,
            'contentType'         => 'json',
            'pollIntervalSeconds' => 5,
        ];

        $options = array_replace($defaults, $options);

        $job = $this->createJob($operation, $objectType, $options['externalIdFieldName'], $options['contentType']);

        if ($job->id) {
            $totalNumberOfBatches = ceil(count($data) / $options['batchSize']);

            for ($i = 1; $i <= $totalNumberOfBatches; $i++) {
                $batches[] = $this->addBatch($job->id, array_splice($data, ($i - 1) * $options['batchSize'], $options['batchSize']));
            }
        }

        $time = time();
        $timeout = $time + $options['batchTimeout'];

        $batches_finished = [];

        while (count($batches_finished) < count($batches) && $time < $timeout) {
            $last_time_start = time();
            foreach ($batches as &$batch) {
                //skip processing if batch is already done processing
                if (in_array($batch->id, $batches_finished)) {
                    continue;
                }

                $batch = $this->batchDetails($job->id, $batch->id);
                if (in_array($batch->state, ['Completed', 'Failed', 'Not Processed'])) {
                    $batchResult = $this->batchResult($job->id, $batch->id);
                    $batch->records = $batchResult->records;
                    $batches_finished[] = $batch->id;
                }
            }

            //if we aren't complete yet, look to sleep for a few seconds so we don't poll constantly
            if (count($batches_finished) < count($batches)) {
                //If the polling for all batches hasn't taken at least the amount of time set for the polling interval, wait the additional time and then continue processing.
                $wait_time = time() - $last_time_start;
                if ($wait_time < $options['pollIntervalSeconds']) {
                    sleep($options['pollIntervalSeconds'] - $wait_time);
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
     *
     * @return BulkJobResponse
     */
    public function createJob($operation, $objectType, $externalIdFieldName = null, $contentType = 'JSON')
    {
        $url = '/services/async/'.SalesforceConfig::get('salesforce.api.version').'/job';

        $json_array = [
            'operation' => $operation,
            'object'    => $objectType,
        ];

        //order of variables matters so this externalIdFieldName has to come before contentType
        if ($operation == 'upsert') {
            $json_array['externalIdFieldName'] = $externalIdFieldName;
        }

        $json_array['contentType'] = $contentType;

        $result = $this->call_api('post', $url, [
            'json' => $json_array,
        ]);

        if ($result && isset($result['id']) && $result['id']) {
            return new BulkJobResponse($result);
        }

        return new BulkJobResponse();
    }

    public function jobDetails($jobId)
    {
        $url = '/services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId;

        $result = $this->call_api('get', $url);

        if ($result && isset($result['id']) && $result['id']) {
            return new BulkJobResponse($result);
        } else {
            //throw exception
        }

        return new BulkJobResponse();
    }

    /**
     * @param $jobId
     *
     * @return BulkJobResponse
     */
    public function closeJob($jobId)
    {
        $url = '/services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId;

        $json_array = [
            'state' => 'Closed',
        ];

        $result = $this->call_api('post', $url, [
            'json' => json_encode($json_array),
        ]);

        if ($result && isset($result['id']) && $result['id']) {
            return new BulkJobResponse($result);
        }

        return new BulkJobResponse();
    }

    /**
     * @param $jobId
     * @param $data
     *
     * @return BulkBatchResponse
     */
    public function addBatch($jobId, $data)
    {
        if (!$jobId) {
            //throw exception
            return new BulkBatchResponse();
        }

        $url = '/services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch';

        $result = $this->call_api('post', $url, [
            'body'    => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK),
            'headers' => [
                'Content-type' => 'application/json',
            ],
        ]);

        if ($result) {
            return new BulkBatchResponse($result);
        }

        return new BulkBatchResponse();
    }

    /**
     * @param $jobId
     * @param $batchId
     *
     * @return BulkBatchResponse
     */
    public function batchDetails($jobId, $batchId)
    {
        $url = '/services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch/'.$batchId;

        $result = $this->call_api('get', $url);

        if ($result && isset($result['id']) && $result['id']) {
            return new BulkBatchResponse($result);
        } else {
            //throw exception
        }

        return new BulkBatchResponse();
    }

    /**
     * @param $jobId
     * @param $batchId
     *
     * @return BulkBatchResultResponse
     */
    public function batchResult($jobId, $batchId)
    {
        if (!$jobId || !$batchId) {
            //throw exception
            return new BulkBatchResultResponse();
        }

        $url = '/services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch/'.$batchId.'/result';

        $result = $this->call_api('get', $url);

        if ($result && is_array($result)) {
            //maximum amount of batch records allowed it 10,000
            for ($i = 0; $i < 10000; $i++) {
                //skip processing for the rest of the records if they don't exist
                if (!isset($result[$i])) {
                    break;
                }
                $result['records'][$i] = $result[$i];
                unset($result[$i]);
            }

            return new BulkBatchResultResponse($result);
        }

        return new BulkBatchResultResponse($result);
    }
}
