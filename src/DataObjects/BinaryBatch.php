<?php

namespace Frankkessler\Salesforce\DataObjects;

use ZipArchive;

class BinaryBatch extends BaseObject
{
    public $batchZip = '';
    /** @var Attachment[] $attachments */
    public $attachments = [];

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $result = [];

        foreach ($this->attachments as $attachment) {
            $result[] = $attachment->toArray();
        }

        return $result;
    }

    public function prepareBatchFile()
    {
        if ($this->batchZip && is_writable($this->batchZip)) {
            $zip = new ZipArchive();
            if ($zip->open($this->batchZip, \ZIPARCHIVE::CREATE) === true) {
                $zip->addFromString('request.txt', json_encode($this->toArray()));
                $zip->close();
            } else {
                throw(new \Exception('Batch zip cannot be opened'));
            }
        } else {
            throw(new \Exception('Batch zip must be defined to use binary batches'));
        }
    }
}
