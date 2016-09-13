<?php

namespace Frankkessler\Salesforce\Responses;

use Frankkessler\Salesforce\DataObjects\SalesforceError;

class SalesforceBaseResponse extends BaseResponse
{
    /**
     * @var boolean
     */
    public $success;

    /**
     * @var integer
     */
    public $http_status_code;

    /**
     * @var string
     */
    public $operation;

    /**
     * @var SalesforceError
     */
    public $error;

    /**
     * @var string
     */
    protected $raw_sfdc_errors;

    /**
     * @var array
     */
    protected $raw_headers;

    /**
     * @var string
     */
    protected $raw_body;

    public function __construct($data=[])
    {
        if(isset($data['raw_sfdc_error'])){
            $this->error = new SalesforceError(json_decode($data['raw_sfdc_error'], true));
        }

        if(isset($data['http_status'])){
            $this->http_status_code = (int)$data['http_status'];
            unset($data['http_status']);
        }
        parent::__construct($data);

        if(!$this->success && $this->http_status_code >= 200 && $this->http_status_code <= 299){
            $this->success = true;
        }
    }

    /**
     * Get Raw Headers from Http Response
     *
     * @return array
     */
    public function getRawHeaders()
    {
        return $this->raw_headers;
    }

    /**
     * Get Raw body from Http Response
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->raw_body;
    }
}