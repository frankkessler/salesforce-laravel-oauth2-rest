<?php

namespace Frankkessler\Salesforce\DataObjects;

use Frankkessler\Salesforce\Contracts\Arrayable;

class BaseObject implements Arrayable
{
    /**
     * @var array
     */
    protected $raw_headers;

    /**
     * @var string
     */
    protected $raw_body;

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
     * Get Additional Fields.
     *
     * @return array
     */
    public function getAdditionalFields()
    {
        return $this->additional_fields;
    }

    /**
     * Get Raw Headers from Http Response.
     *
     * @return array
     */
    public function getRawHeaders()
    {
        return $this->raw_headers;
    }

    /**
     * Get Raw body from Http Response.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->raw_body;
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
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArrayAll()
    {
        $return = get_object_vars($this);

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
