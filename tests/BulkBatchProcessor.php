<?php

use Frankkessler\Salesforce\Interfaces\BulkBatchProcessorInterface;
use Frankkessler\Salesforce\Responses\Bulk\BulkBatchResultResponse;

class BulkBatchProcessor implements BulkBatchProcessorInterface
{
    public static $records = [];

    public static function process(BulkBatchResultResponse $batchResult)
    {
        static::$records = array_merge(static::$records, $batchResult->records);
    }
}