<?php

namespace Frankkessler\Salesforce;

use Frankkessler\Salesforce\Responses\Sobject\SobjectDeleteResponse;
use Frankkessler\Salesforce\Responses\Sobject\SobjectGetResponse;
use Frankkessler\Salesforce\Responses\Sobject\SobjectInsertResponse;
use Frankkessler\Salesforce\Responses\Sobject\SobjectUpdateResponse;

class Sobject
{
    /**
     * @var Salesforce
     */
    private $oauth2Client;

    public function __construct($oauth2Client)
    {
        $this->oauth2Client = $oauth2Client;
    }

    /**
     * Get full sObject.
     *
     * @param $id
     * @param $type
     *
     * @return SobjectGetResponse
     */
    public function get($id, $type)
    {
        return new SobjectGetResponse(
            $this->oauth2Client->call_api('get', 'sobjects/'.$type.'/'.$id)
        );
    }

    /**
     * Create sObject.
     *
     * @param string $type
     * @param array  $data
     *
     * @return SobjectInsertResponse
     */
    public function insert($type, $data)
    {
        return new SobjectInsertResponse(
            $this->oauth2Client->call_api('post', 'sobjects/'.$type, [
                'http_errors' => false,
                'body'        => json_encode($data),
                'headers'     => [
                    'Content-type' => 'application/json',
                ],
            ])
        );
    }

    /**
     * Update sObject.
     *
     * @param string $id
     * @param string $type
     * @param array  $data
     *
     * @return SobjectUpdateResponse
     */
    public function update($id, $type, $data)
    {
        if (!$id && isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
        } elseif (isset($data['id'])) {
            unset($data['id']);
        }

        if (!$id || !$type || !$data) {
            return new SobjectUpdateResponse([]);
        }

        return new SobjectUpdateResponse(
            $this->oauth2Client->call_api('patch', 'sobjects/'.$type.'/'.$id, [
                'http_errors' => false,
                'body'        => json_encode($data),
                'headers'     => [
                    'Content-type' => 'application/json',
                ],
            ])
        );
    }

    /**
     * @param $id
     * @param $type
     *
     * @return array|SobjectDeleteResponse
     */
    public function delete($id, $type)
    {
        if (!$type || !$id) {
            return new SobjectDeleteResponse();
        }

        return new SobjectDeleteResponse(
            $this->oauth2Client->call_api('delete', 'sobjects/'.$type.'/'.$id)
        );
    }

    public function externalGet($external_field_name, $external_id, $type)
    {
        return new SobjectGetResponse(
            $this->oauth2Client->call_api('get', 'sobjects/'.$type.'/'.$external_field_name.'/'.$external_id)
        );
    }

    public function externalUpsert($external_field_name, $external_id, $type, $data)
    {
        return new SobjectInsertResponse(
            $this->oauth2Client->call_api('patch', 'sobjects/'.$type.'/'.$external_field_name.'/'.$external_id, [
                'http_errors' => false,
                'body'        => json_encode($data),
                'headers'     => [
                    'Content-type' => 'application/json',
                ],
            ])
        );
    }
}
