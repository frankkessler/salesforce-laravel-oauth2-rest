<?php

namespace Frankkessler\Salesforce\DataObjects;

use ZipArchive;

class BinaryBatch extends BaseObject
{
    public $batchZip = '';
    /** @var Attachment[] $attachments */
    public $attachments = [];

    public $format = 'csv';

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
                if ($this->format == 'csv') {
                    $request_array = $this->createCsvArray($this->toArray());
                    $request_string = str_putcsv($request_array);
                } else {
                    $request_string = json_encode($this->toArray());
                }
                $zip->addFromString('request.txt', $request_string);
                $zip->close();
            } else {
                throw(new \Exception('Batch zip cannot be opened'));
            }
        } else {
            throw(new \Exception('Batch zip must be defined to use binary batches'));
        }
    }

    public function createCsvArray($input)
    {
        $i = 1;
        $result = [];

        foreach ($input as $row) {
            if ($i == 1) {
                $result[] = array_keys($row);
            }
            $result[] = array_values($row);
            $i++;
        }

        return $result;
    }
}
