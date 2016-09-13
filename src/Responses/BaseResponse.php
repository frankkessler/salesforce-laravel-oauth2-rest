<?php

namespace Frankkessler\Salesforce\Responses;

use Frankkessler\Salesforce\Contracts\Arrayable;

class BaseResponse implements Arrayable
{
    private $additional_fields = [];

    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key) && $key != 'additional_fields') {
                $this->{$key} = $value;
            } else {
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

    /**
     * __toString implementation for this class.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }
}
