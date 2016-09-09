<?php

namespace Frankkessler\Salesforce;

use CommerceGuys\Guzzle\Oauth2\BulkClient;
use Frankkessler\Salesforce\Responses\BulkBatchResponse;
use Frankkessler\Salesforce\Responses\BulkJobResponse;

class Bulk extends Salesforce
{
    public function __construct($config = [])
    {
        $base_uri = 'https://'.SalesforceConfig::get('salesforce.api.domain').SalesforceConfig::get('salesforce.api.base_uri');
        $this->oauth2Client = new BulkClient([
            'base_uri' => $base_uri,
            'auth'     => 'bulk',
        ]);
        parent::__construct($config);
    }

    public function runBatch($operation, $objectType, $data, $batchSize = 2000, $batchTimeout = 600, $contentType = 'json', $pollIntervalSeconds = 5)
    {
        $batchIds = [];
        $batches = [];

        $jobId = $this->createJob($operation, $objectType, $contentType);

        $totalNumberOfBatches = ceil(count($data) / $batchSize);

        for ($i = 1; $i <= $totalNumberOfBatches; $i++) {
            $batchIds[] = $this->addBatch($jobId, $data);
        }


        for ($i = 0; $i < $batchTimeout / 5; $i++) {
            $time = time();
            foreach ($batchIds as $batchId) {
                $batchDetails = $this->batchDetails($jobId, $batchId);
                if (in_array($batchDetails->state, ['Completed', 'Failed', 'Not Processed'])) {
                    $batchDetails->records = $this->batchResult($jobId, $batchId);
                    $batches[] = $batchDetails;
                }
            }

            //If the polling for all batches hasn't taken at least the amount of time set for the polling interval, wait the additional time and then continue processing.
            $wait_time = time() - $time;
            if ($wait_time < $pollIntervalSeconds) {
                sleep($pollIntervalSeconds - $wait_time);
            }
        }

        $jobDetails = $this->jobDetails($jobId);
        $jobDetails->batches = $batches;

        return $jobDetails;
    }

    public function createJob($operation, $objectType, $contentType = 'json')
    {
        $url = 'services/async/'.SalesforceConfig::get('salesforce.api.version').'/job';
        $json_array = [
            'operation'   => $operation,
            'object'      => $objectType,
            'contentType' => $contentType,
        ];

        $result = $this->call_api('post', $url, [
            'body'    => json_encode($json_array),
            'headers' => [
                'Content-type' => 'application/json',
            ],
        ]);

        if ($result && isset($result['id']) && $result['id']) {
            return $result['id'];
        }
    }

    public function jobDetails($jobId)
    {
        $url = 'services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId;

        $result = $this->call_api('get', $url);

        if ($result && isset($result['id']) && $result['id']) {
            return new BulkJobResponse($result);
        } else {
            //throw exception
        }

        return new BulkJobResponse();
    }

    public function closeJob($jobId)
    {
        $url = 'services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId;

        $json_array = [
            'state' => 'Closed',
        ];

        $result = $this->call_api('post', $url, [
            'body'    => json_encode($json_array),
            'headers' => [
                'Content-type' => 'application/json',
            ],
        ]);

        if ($result && isset($result['id']) && $result['id']) {
            return $result['id'];
        }
    }

    public function addBatch($jobId, $data)
    {
        $url = 'services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch';

        $result = $this->call_api('post', $url, [
            'body'    => json_encode($data),
            'headers' => [
                'Content-type' => 'application/json',
            ],
        ]);

        if ($result && isset($result['id']) && $result['id']) {
            return $result['id'];
        }
    }

    /**
     * @param $jobId
     * @param $batchId
     *
     * @return BulkBatchResponse
     */
    public function batchDetails($jobId, $batchId)
    {
        $url = 'services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch/'.$batchId;

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
     * @return array
     */
    public function batchResult($jobId, $batchId)
    {
        $url = 'services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch/'.$batchId.'/result';

        $result = $this->call_api('get', $url);

        if ($result && is_array($result)) {
            return $result;
        }

        return [];
    }
}
