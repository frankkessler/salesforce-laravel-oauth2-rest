<?php

namespace Frankkessler\Salesforce\Responses\Bulk;

use Frankkessler\Salesforce\DataObjects\BaseObject;

class BulkBatchResponse extends BaseObject
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
