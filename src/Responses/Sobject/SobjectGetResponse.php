<?php

namespace Frankkessler\Salesforce\Responses\Sobject;

use Frankkessler\Salesforce\Responses\SalesforceBaseResponse;

class SobjectGetResponse extends SalesforceBaseResponse
{
    /**
     * @var array
     */
    public $sobject;

    public function __construct($data)
    {
        $this->sobject = json_decode($data['raw_body']);

        parent::__construct($data);
    }
}
