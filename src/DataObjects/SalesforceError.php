<?php

namespace Frankkessler\Salesforce\DataObjects;

class SalesforceError
{
    /**
     * @var array
     */
    public $fields;

    /**
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $errorCode;

    public function __construct($data)
    {
        if (count($data) == 1) {
            $data = current($data);
        }
        $this->fields = isset($data['fields']) ? $data['fields'] : '';
        $this->message = isset($data['message']) ? $data['message'] : '';
        $this->errorCode = isset($data['errorCode']) ? $data['errorCode'] : '';
    }

    public function isValid()
    {
        if ($this->message || $this->errorCode) {
            return true;
        }

        return false;
    }
}
