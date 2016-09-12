<?php

namespace Frankkessler\Salesforce;

class SalesforceResponse
{
    public $success;
    public $status_code;
    public $operation;
    public $message_string;
    public $file;
    public $id;
    public $records;

    private $additional_fields;

    /**
     * Treatment constructor.
     * @param array $data
     * @param array $defaults
     * @param array $overrides
     */
    public function __construct($data=[])
    {
        if(!is_array($data)){
            $data = [];
        }

        foreach($data as $key=>$value){
            if(property_exists($this,$key) && $key != 'additional_fields'){
                $this->{$key} = $value;
            }else{
                $this->additional_fields[$key] = $value;
            }
        }
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $return = get_object_public_vars($this);

        return $return;
    }
}