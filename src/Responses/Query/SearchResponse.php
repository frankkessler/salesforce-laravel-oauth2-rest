<?php

namespace Frankkessler\Salesforce\Responses\Query;

use Frankkessler\Salesforce\Responses\SalesforceBaseResponse;

class SearchResponse extends SalesforceBaseResponse
{
    /**
     * @var array
     */
    public $records;

    /**
     * @var int
     */
    public $totalSize;

    public function __construct($data)
    {
        parent::__construct($data);

        $this->records = json_decode($data['raw_body'], true);

        $this->totalSize = count($this->records);
    }
}
