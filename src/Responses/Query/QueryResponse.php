<?php

namespace Frankkessler\Salesforce\Responses\Query;

use Frankkessler\Salesforce\Responses\SalesforceBaseResponse;

class QueryResponse extends SalesforceBaseResponse
{
    /**
     * @var integer
     */
    public $totalSize;

    /**
     * @var boolean
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