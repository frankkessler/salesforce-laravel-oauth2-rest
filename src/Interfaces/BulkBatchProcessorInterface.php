<?php

namespace Frankkessler\Salesforce\Interfaces;

use Frankkessler\Salesforce\Responses\Bulk\BulkBatchResultResponse;

interface BulkBatchProcessorInterface
{
    public static function process(BulkBatchResultResponse $batchResult);
}
