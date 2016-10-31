<?php

namespace Frankkessler\Salesforce\Responses\Bulk;

use Frankkessler\Salesforce\DataObjects\BaseObject;

class BulkJobResponse extends BaseObject
{
    public $apexProcessingTime = 0;
    public $apiActiveProcessingTime = 0;
    public $apiVersion;
    public $concurrencyMode;
    public $contentType;
    public $createdById;
    public $createdDate;
    public $id;
    public $numberBatchesCompleted = 0;
    public $numberBatchesFailed = 0;
    public $numberBatchesInProgress = 0;
    public $numberBatchesQueued = 0;
    public $numberBatchesTotal = 0;
    public $numberRecordsFailed = 0;
    public $numberRecordsProcessed = 0;
    public $numberRetries = 0;
    public $object;
    public $operation;
    public $state;
    public $systemModstamp;
    public $totalProcessingTime = 0;
    public $batches;
}
