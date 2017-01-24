<?php

namespace Frankkessler\Salesforce;

use Frankkessler\Salesforce\Client\BulkClient;
use Frankkessler\Salesforce\DataObjects\BinaryBatch;
use Frankkessler\Salesforce\Responses\Bulk\BulkBatchResponse;
use Frankkessler\Salesforce\Responses\Bulk\BulkBatchResultResponse;
use Frankkessler\Salesforce\Responses\Bulk\BulkJobResponse;

class Bulk extends Salesforce
{
    public function __construct($config = [])
    {
        if (isset($config['bulk_base_uri'])) {
            $base_uri = $config['bulk_base_uri'];
        } else {
            $base_uri = 'https://'.SalesforceConfig::get('salesforce.api.domain');
        }

        $client_config = [
            'base_uri' => $base_uri,
            'auth'     => 'bulk',
        ];

        if (isset($config['handler'])) {
            $client_config['handler'] = $config['handler'];
        }

        $this->oauth2Client = new BulkClient($client_config);

        parent::__construct(array_replace($config, $client_config));
    }

    public function runBatch($operation, $objectType, $data, $options = [])
    {
        $batches = [];

        $defaults = [
            'externalIdFieldName'       => null,
            'batchSize'                 => 2000,
            'batchTimeout'              => 600,
            'contentType'               => 'JSON',
            'pollIntervalSeconds'       => 5,
            'isBatchedResult'           => false,
            'concurrencyMode'           => 'Parallel',
            'Sforce-Enable-PKChunking'  => false,
            'batchProcessor'            => null,
        ];

        $options = array_replace($defaults, $options);

        if ($operation == 'query') {
            $options['isBatchedResult'] = true;
        }

        $job = $this->createJob($operation, $objectType, $options['externalIdFieldName'], $options['contentType'], $options['concurrencyMode'], $options);

        if ($job->id) {
            //if data is array, we can split it into batches
            if (is_array($data)) {
                $totalNumberOfBatches = ceil(count($data) / $options['batchSize']);
                $this->log('info', 'Job Record Count: '.count($data).' Number of Batches: '.$totalNumberOfBatches);
                for ($i = 1; $i <= $totalNumberOfBatches; $i++) {
                    $batches[] = $this->addBatch($job->id, array_splice($data, 0, $options['batchSize']));
                }
            } else { //probably a string query so run in one batch
                $batches[] = $this->addBatch($job->id, $data);
            }
        } else {
            $this->log('error', 'Job Failed: '.json_encode($job->toArrayAll()));
        }

        $time = time();
        $timeout = $time + $options['batchTimeout'];

        if ($options['Sforce-Enable-PKChunking']) {
            $batches = $this->allBatchDetails($job->id, $options['contentType']);
        }

        $batches_finished = [];

        while (count($batches_finished) < count($batches) && $time < $timeout) {
            $last_time_start = time();
            foreach ($batches as &$batch) {
                //skip processing if batch is already done processing
                if (in_array($batch->id, $batches_finished)) {
                    continue;
                }

                $batch = $this->batchDetails($job->id, $batch->id, $options['contentType']);
                if (in_array($batch->state, ['Completed', 'Failed', 'Not Processed', 'NotProcessed'])) {
                    if (in_array($batch->state, ['Completed'])) {
                        $batchResult = $this->batchResult($job->id, $batch->id, $options['isBatchedResult'], null, $options['contentType']);
                        if (class_exists($options['batchProcessor']) && class_implements($options['batchProcessor'], '\Frankkessler\Salesforce\Interfaces\BulkBatchProcessorInterface')) {
                            call_user_func([$options['batchProcessor'], 'process'], $batchResult);
                        } else {
                            $batch->records = $batchResult->records;
                        }
                    }
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

        //only close the job is all batches finished
        if (count($batches_finished) == count($batches)) {
            $job = $this->closeJob($job->id);
        }

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
    public function createJob($operation, $objectType, $externalIdFieldName = null, $contentType = 'JSON', $concurrencyMode = 'Parallel', $options = [])
    {
        $url = '/services/async/'.SalesforceConfig::get('salesforce.api.version').'/job';

        $json_array = [
            'operation'       => $operation,
            'object'          => $objectType,
            'concurrencyMode' => $concurrencyMode,
        ];

        $headers = [];

        if (isset($options['Sforce-Enable-PKChunking']) && $options['Sforce-Enable-PKChunking']) {
            $headers['Sforce-Enable-PKChunking'] = $this->parsePkChunkingHeader($options['Sforce-Enable-PKChunking']);
        }

        //order of variables matters so this externalIdFieldName has to come before contentType
        if ($operation == 'upsert') {
            $json_array['externalIdFieldName'] = $externalIdFieldName;
        }

        $json_array['contentType'] = $contentType;

        $result = $this->call_api('post', $url, [
            'json'    => $json_array,
            'headers' => $headers,
        ]);

        if ($result && is_array($result)) {
            return new BulkJobResponse($result);
        }

        return new BulkJobResponse();
    }

    public function jobDetails($jobId, $format = 'json')
    {
        $url = '/services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId;

        $result = $this->call_api('get', $url,
            [
                'format'  => $this->batchResponseFormatFromContentType($format),
            ]);

        if ($result && is_array($result)) {
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
            'json' => $json_array,
        ]);

        if ($result && is_array($result)) {
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
    public function addBatch($jobId, $data, $format = 'json')
    {
        if (!$jobId) {
            //throw exception
            return new BulkBatchResponse();
        }

        $url = '/services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch';

        $headers = [];
        //json_encode any arrays to send over to bulk api
        if (is_array($data)) {
            $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            $headers = [
                'Content-type' => 'application/json',
            ];
        } else {
            $body = $data;
        }

        $result = $this->call_api('post', $url, [
            'body'    => $body,
            'headers' => $headers,
            'format'  => $this->batchResponseFormatFromContentType($format),
        ]);

        if ($result && is_array($result)) {
            return new BulkBatchResponse($result);
        }

        return new BulkBatchResponse();
    }

    /**
     * @param $jobId
     * @param $batchId
     * @param $format
     *
     * @return BulkBatchResponse
     */
    public function batchDetails($jobId, $batchId, $format = 'json')
    {
        $url = '/services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch/'.$batchId;

        $result = $this->call_api('get', $url, [
            'format'  => $this->batchResponseFormatFromContentType($format),
        ]);

        if ($result && is_array($result)) {
            return new BulkBatchResponse($result);
        } else {
            //throw exception
        }

        return new BulkBatchResponse();
    }

    /**
     * @param $jobId
     * @param $format
     *
     * @return BulkBatchResponse[]
     */
    public function allBatchDetails($jobId, $format = 'json')
    {
        $batches = [];

        //TODO:  Fix hack to give initial Salesforce batch time to split into many batches by PK
        sleep(10);
        ////////////////////////////////////////////////////////////////////////////////////////

        $url = '/services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch';

        $result = $this->call_api('get', $url, [
            'format'  => $this->batchResponseFormatFromContentType($format),
        ]);

        if ($result && is_array($result) && isset($result['batchInfo']) && !isset($result['batchInfo']['id'])) {
            foreach ($result['batchInfo'] as $batch) {
                $batches[] = new BulkBatchResponse($batch);
            }
        } else {
            //throw exception
        }

        return $batches;
    }

    /**
     * @param $jobId
     * @param $batchId
     *
     * @return BulkBatchResultResponse
     */
    public function batchResult($jobId, $batchId, $isBatchedResult = false, $resultId = null, $format = 'json')
    {
        if (!$jobId || !$batchId) {
            //throw exception
            return new BulkBatchResultResponse();
        }

        $url = '/services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch/'.$batchId.'/result';

        $resultPostArray = [];

        //if this is a query result, the main result page will have an array of result ids to follow for hte query results
        if ($resultId) {
            $url = $url.'/'.$resultId;
            //results returned in contentType supplied by the creation of the job
            $resultPostArray['format'] = $format;
            //all results that have a $resultId should be returned without lowercase formatting
            $resultPostArray['lowerCaseHeaders'] = false;
        } else {
            //result object returned in xml if selecting csv as contentType
            $resultPostArray['format'] = $this->batchResponseFormatFromContentType($format);
        }

        $result = $this->call_api('get', $url, $resultPostArray);

        if ($result && is_array($result)) {

            //initialize array for records to be used later
            if (!isset($result['records']) || !is_array($result['records'])) {
                $result['records'] = [];
            }

            if (isset($result['result'])) {
                if (!is_array($result['result'])) {
                    $result['result'] = [$result['result']];
                }
                $result = array_merge($result, $result['result']);
            }

            //maximum amount of batch records allowed is 10,000
            for ($i = 0; $i < 10000; $i++) {
                //skip processing for the rest of the records if they don't exist
                if (!isset($result[$i])) {
                    break;
                }

                //batched results return a list of result ids that need to be processed to get the actual data
                if ($isBatchedResult) {
                    $batchResult = $this->batchResult($jobId, $batchId, false, $result[$i], $format);
                    $result['records'] = array_merge($result['records'], $batchResult->records);
                } else {
                    //fix boolean values from appearing as
                    foreach (['success', 'created'] as $field) {
                        if (isset($result[$i][$field])) {
                            if ($result[$i][$field] == 'true') {
                                $result[$i][$field] = true;
                            } else {
                                $result[$i][$field] = false;
                            }
                        }
                    }

                    $result['records'][$i] = $result[$i];
                }

                unset($result[$i]);
            }

            return new BulkBatchResultResponse($result);
        }

        return new BulkBatchResultResponse($result);
    }

    /******* BINARY SPECIFIC FUNCTIONS *********/

    /**
     * @param $operation
     * @param $objectType
     * @param BinaryBatch[] $binaryBatches
     * @param array         $options
     *
     * @throws \Exception
     *
     * @return BulkJobResponse
     */
    public function runBinaryUploadBatch($operation, $objectType, $binaryBatches, $options = [])
    {
        $batches = [];

        $defaults = [
            'batchTimeout'        => 600,
            'contentType'         => 'ZIP_CSV',
            'pollIntervalSeconds' => 5,
            'isBatchedResult'     => false,
            'concurrencyMode'     => 'Parallel',
        ];

        $options = array_replace($defaults, $options);

        if ($operation == 'query') {
            $options['isBatchedResult'] = true;
        }

        $job = $this->createJob($operation, $objectType, null, $options['contentType'], $options['concurrencyMode']);

        if ($job->id) {
            //if data is array, we can split it into batches
            if (is_array($binaryBatches)) {
                foreach ($binaryBatches as $binaryBatch) {
                    $batches[] = $this->addBinaryBatch($job->id, $binaryBatch);
                }
            } else { //probably a string query so run in onee batch
                throw(new \Exception('$binaryBatches must be an array'));
            }
        } else {
            $this->log('error', 'Job Failed: '.json_encode($job->toArrayAll()));
        }

        $time = time();
        $timeout = $time + $options['batchTimeout'];

        $batches_finished = [];

        $resultFormat = strtolower(explode('_', $options['contentType'])[1]);
        while (count($batches_finished) < count($batches) && $time < $timeout) {
            $last_time_start = time();
            foreach ($batches as &$batch) {
                //skip processing if batch is already done processing
                if (in_array($batch->id, $batches_finished)) {
                    continue;
                }

                $batch = $this->batchDetails($job->id, $batch->id, $this->batchResponseFormatFromContentType($options['contentType']));

                if (in_array($batch->state, ['Completed', 'Failed', 'Not Processed'])) {
                    $batches_finished[] = $batch->id;
                    $batchResult = $this->batchResult($job->id, $batch->id, $options['isBatchedResult'], null, $resultFormat);
                    $batch->records = $batchResult->records;
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

        //only close the job is all batches finished
        if (count($batches_finished) == count($batches)) {
            $job = $this->closeJob($job->id);
        }

        $job->batches = $batches;

        return $job;
    }

    /**
     * @param $jobId
     * @param BinaryBatch $binaryBatch
     *
     * @return BulkBatchResponse
     */
    public function addBinaryBatch($jobId, BinaryBatch $binaryBatch, $contentType = 'zip/csv')
    {
        if (!$jobId) {
            //throw exception
            return new BulkBatchResponse();
        }

        $binaryBatch->prepareBatchFile();

        $url = '/services/async/'.SalesforceConfig::get('salesforce.api.version').'/job/'.$jobId.'/batch';

        $body = file_get_contents($binaryBatch->batchZip);
        $headers = [
            'Content-type' => $contentType,
        ];

        $result = $this->call_api('post', $url, [
            'body' => $body,
            //'body'    => fopen($binaryBatch->batchZip,'rb'),
            'headers' => $headers,
            'format'  => $this->batchResponseFormatFromContentType($contentType),
        ]);

        if ($result && is_array($result)) {
            return new BulkBatchResponse($result);
        }

        return new BulkBatchResponse();
    }

    public function batchResponseFormatFromContentType($contentType)
    {
        switch (strtoupper($contentType)) {
            case 'ZIP_CSV':
            case 'ZIP/CSV':
            case 'ZIP_XML':
            case 'ZIP/XML':
            case 'CSV':
            case 'XML':
                $return = 'xml';
                break;
            default:
                $return = 'json';
                break;
        }

        return $return;
    }

    public function parsePkChunkingHeader($pk_chunk_header)
    {
        if (is_array($pk_chunk_header)) {
            $header_parts = [];
            foreach ($pk_chunk_header as $key => $value) {
                $header_parts[] = $key.'='.$value;
            }

            return implode('; ', $header_parts);
        } elseif (in_array($pk_chunk_header, [true, 'true', 'TRUE'])) {
            return 'TRUE';
        }

        return 'FALSE';
    }
}
