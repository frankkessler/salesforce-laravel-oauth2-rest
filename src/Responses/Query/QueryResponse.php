<?php

namespace Frankkessler\Salesforce\Responses\Query;

use Frankkessler\Salesforce\Responses\SalesforceBaseResponse;

class QueryResponse extends SalesforceBaseResponse
{
    /**
     * @var int
     */
    public $totalSize;

    /**
     * @var bool
     */
    public $done;

    /**
     * @var array
     */
    public $records;

    /**
     * @var string
     */
    public $nextRecordsUrl;
}
