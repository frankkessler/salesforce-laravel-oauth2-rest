<?php

namespace Frankkessler\Salesforce\Responses;

class BulkBatchResponse extends BaseResponse
{
    public $apexProcessingTime = 0;
    public $apiActiveProcessingTime = 0;
    public $createdDate;
    public $id;
    public $jobId;
    public $numberRecordsFailed = 0;
    public $numberRecordsProcessed = 0;
    public $state;
    public $systemModstamp;
    public $totalProcessingTime = 0;
    public $records;
}