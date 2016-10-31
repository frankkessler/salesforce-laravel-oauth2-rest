<?php

namespace Frankkessler\Salesforce\DataObjects;

class Attachment extends BaseObject
{
    public $Name;
    public $ParentId;
    public $Body;
    public $ContentType;
    public $Description;

    private $localFilePath;

    public function getLocalFilePath()
    {
        return $this->localFilePath;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $return = get_object_public_vars($this);

        foreach ($return as $key => $value) {
            if (is_null($value)) {
                unset($return[$key]);
            }
        }

        return $return;
    }
}
