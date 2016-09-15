<?php

namespace Frankkessler\Salesforce;

use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Oauth2\Oauth2Client;
use Exception;
use Frankkessler\Salesforce\Repositories\TokenRepository;

class Salesforce
{
    public $oauth2Client;

    protected $config;

    /**
     * @var Sobject
     */
    private $sobject_api;

    /**
     * @var Query
     */
    private $query_api;

    /**
     * @var Custom
     */
    private $custom_api;

    /**
     * @var Bulk
     */
    private $bulk_api;

    public function __construct($config = null)
    {
        //Allow custom config to be applied through the constructor
        SalesforceConfig::setInitialConfig($config);

        $this->config = SalesforceConfig::get();

        $this->repository = new TokenRepository();

        $base_uri = 'https://'.SalesforceConfig::get('salesforce.api.domain').SalesforceConfig::get('salesforce.api.base_uri');

        $client_config = [
            'base_uri' => $base_uri,
            'auth'     => 'oauth2',
        ];

        //allow for override of default oauth2 handler
        if (isset($config['handler'])) {
            $client_config['handler'] = $config['handler'];
        }

        if (!$this->oauth2Client) {
            $this->oauth2Client = new Oauth2Client($client_config);
        }

        //If access_token or refresh_token are NOT supplied through constructor, pull them from the repository
        if (!SalesforceConfig::get('salesforce.oauth.access_token') || !SalesforceConfig::get('salesforce.oauth.refresh_token')) {
            $this->token_record = $this->repository->store->getTokenRecord();
            SalesforceConfig::set('salesforce.oauth.access_token', $this->token_record->access_token);
            SalesforceConfig::set('salesforce.oauth.refresh_token', $this->token_record->refresh_token);
        }

        $access_token = SalesforceConfig::get('salesforce.oauth.access_token');
        $refresh_token = SalesforceConfig::get('salesforce.oauth.refresh_token');

        //Set access token and refresh token in Guzzle oauth client
        $this->oauth2Client->setAccessToken($access_token, $access_token_type = 'Bearer');
        $this->oauth2Client->setRefreshToken($refresh_token);
        $refresh_token_config = [
            'client_id'     => SalesforceConfig::get('salesforce.oauth.consumer_token'),
            'client_secret' => SalesforceConfig::get('salesforce.oauth.consumer_secret'),
            'refresh_token' => $refresh_token,
            'token_url'     => 'https://'.SalesforceConfig::get('salesforce.oauth.domain').SalesforceConfig::get('salesforce.oauth.token_uri'),
            'auth_location' => 'body',
        ];
        $this->oauth2Client->setRefreshTokenGrantType(new RefreshToken($refresh_token_config));
    }

    /**
     * Get full sObject (DEPRECATED).
     *
     * @deprecated
     * @codeCoverageIgnore
     *
     * @param $id
     * @param $type
     *
     * @return array|mixed
     */
    public function getObject($id, $type)
    {
        $result = $this->sobject()->get($id, $type);

        $array_result = array_replace($result->toArray(), $result->sobject);

        if ($result->error->isValid()) {
            $array_result['message_string'] = $result->error->message;
        }

        return $array_result;
    }

    /**
     * Create sObject (DEPRECATED).
     *
     * @deprecated
     * @codeCoverageIgnore
     *
     * @param string $type
     * @param array  $data
     *
     * @return array|mixed
     */
    public function createObject($type, $data)
    {
        $result = $this->sobject()->insert($type, $data);

        $array_result = $result->toArray();

        $array_result['Id'] = $result->id;

        if ($result->error->isValid()) {
            $array_result['message_string'] = $result->error->message;
        }

        return $array_result;
    }

    /**
     * Update sObject (DEPRECATED).
     *
     * @deprecated
     * @codeCoverageIgnore
     *
     * @param string $id
     * @param string $type
     * @param array  $data
     *
     * @return array|mixed
     */
    public function updateObject($id, $type, $data)
    {
        $result = $this->sobject()->update($id, $type, $data);

        $array_result = $result->toArray();

        if ($result->error->isValid()) {
            $array_result['message_string'] = $result->error->message;
        }

        return $array_result;
    }

    /**
     * Delete Object (DEPRECATED).
     *
     * @deprecated
     * @codeCoverageIgnore
     *
     * @param $id
     * @param $type
     *
     * @return array
     */
    public function deleteObject($id, $type)
    {
        $result = $this->sobject()->delete($id, $type);

        $array_result = $result->toArray();

        if ($result->error->isValid()) {
            $array_result['message_string'] = $result->error->message;
        }

        return $array_result;
    }

    /**
     * Get Object by External Id (DEPRECATED).
     *
     * @deprecated
     * @codeCoverageIgnore
     *
     * @param $external_field_name
     * @param $external_id
     * @param $type
     *
     * @return array
     */
    public function externalGetObject($external_field_name, $external_id, $type)
    {
        $result = $this->sobject()->externalGet($external_field_name, $external_id, $type);

        $array_result = $result->toArray();

        if ($result->error->isValid()) {
            $array_result['message_string'] = $result->error->message;
        }

        return $array_result;
    }

    /**
     * Upsert an object by an External Id.
     *
     * @deprecated
     * @codeCoverageIgnore
     *
     * @param $external_field_name
     * @param $external_id
     * @param $type
     * @param $data
     *
     * @return array
     */
    public function externalUpsertObject($external_field_name, $external_id, $type, $data)
    {
        $result = $this->sobject()->externalUpsert($external_field_name, $external_id, $type, $data);

        $array_result = $result->toArray();

        $array_result['Id'] = $result->id;

        if ($result->error->isValid()) {
            $array_result['message_string'] = $result->error->message;
        }

        return $array_result;
    }

    /**
     * SOQL Query (DEPRECATED).
     *
     * @deprecated
     * @codeCoverageIgnore
     *
     * @param $query
     *
     * @return array
     */
    public function query_legacy($query)
    {
        return $this->query()->query($query)->toArray();
    }

    /**
     * SOQL Query and follow next URL until all records are gathered (DEPRECATED).
     *
     * @deprecated
     * @codeCoverageIgnore
     *
     * @param $query
     *
     * @return array
     */
    public function queryFollowNext($query)
    {
        return $this->query()->queryFollowNext($query)->toArray();
    }

    /**
     * SOQL Query including deleted records (DEPRECATED).
     *
     * @deprecated
     * @codeCoverageIgnore
     *
     * @param $query
     *
     * @return array
     */
    public function queryAll($query)
    {
        return $this->query()->queryAll($query)->toArray();
    }

    /**
     * SOQL Query including deleted records and follow next URL until all records are gathered (DEPRECATED).
     *
     * @deprecated
     * @codeCoverageIgnore
     *
     * @param $query
     *
     * @return array
     */
    public function queryAllFollowNext($query)
    {
        return $this->query()->queryAllFollowNext($query)->toArray();
    }

    /**
     * SOSL Query (DEPRECATED).
     *
     * @deprecated
     * @codeCoverageIgnore
     *
     * @param $query
     *
     * @return array
     */
    public function search($query)
    {
        //TODO: put response records into records parameter
        return $this->query()->search($query)->records;
    }

    /**
     * GET request for custom APEX web service endpoint (DEPRECATED.
     *
     * @deprecated
     * @codeCoverageIgnore
     *
     * @param $uri
     *
     * @return mixed
     */
    public function getCustomRest($uri)
    {
        return $this->custom()->get($uri);
    }

    /**
     * POST request for custom APEX web service endpoint (DEPRECATED).
     *
     * @deprecated
     * @codeCoverageIgnore
     *
     * @param $uri
     * @param $data
     *
     * @return mixed
     */
    public function postCustomRest($uri, $data)
    {
        return $this->custom()->post($uri, $data);
    }

    public function rawGetRequest($request_string)
    {
        return $this->call_api('get', $request_string);
    }

    public function rawPostRequest($request_string, $data)
    {
        return $this->call_api('post', $request_string, [
            'http_errors' => false,
            'body'        => json_encode($data),
            'headers'     => [
                'Content-type' => 'application/json',
            ],
        ]);
    }

    /**
     * @return Sobject
     */
    public function sobject()
    {
        if (!$this->sobject_api) {
            $this->sobject_api = new Sobject($this);
        }

        return $this->sobject_api;
    }

    /**
     * @return Query
     */
    public function query($legacy_query = null)
    {
        //TODO: DEPRECATE IN NEXT RELEASE
        if ($legacy_query) {
            return $this->query_legacy($legacy_query);
        }

        if (!$this->query_api) {
            $this->query_api = new Query($this);
        }

        return $this->query_api;
    }

    /**
     * @return Custom
     */
    public function custom()
    {
        if (!$this->custom_api) {
            $this->custom_api = new Custom($this);
        }

        return $this->custom_api;
    }

    /**
     * @return Bulk
     */
    public function bulk()
    {
        if (!$this->bulk_api) {
            $this->bulk_api = new Bulk($this->config);
        }

        return $this->bulk_api;
    }

    /**
     * Api Call to Salesforce.
     *
     * @param $method
     * @param $url
     * @param array $options
     * @param array $debug_info
     *
     * @return array
     */
    public function call_api($method, $url, $options = [], $debug_info = [])
    {
        try {
            if (is_null($options)) {
                $options = [];
            }

            $options['http_errors'] = false;

            $response = $this->oauth2Client->{$method}($url, $options);

            /* @var $response \GuzzleHttp\Psr7\Response */

            $headers = $response->getHeaders();

            $response_code = $response->getStatusCode();

            $data = [
                'operation'      => '',
                'success'        => false,
                'message_string' => '',
                'http_status'    => $response_code,
                'raw_headers'    => $headers,
                'raw_body'       => (string) $response->getBody(),
            ];

            if ($response_code == 200) {
                $data = array_replace($data, json_decode((string) $response->getBody(), true));
            } elseif ($response_code == 201) {
                $data = array_replace($data, json_decode((string) $response->getBody(), true));

                $data['operation'] = 'create';

                if (isset($data['id'])) {
                    //make responses more uniform by setting a newly created id as an Id field like you would see from a get
                    $data['Id'] = $data['id'];
                }
            } elseif ($response_code == 204) {
                if (strtolower($method) == 'delete') {
                    $data = array_merge($data, [
                        'success'     => true,
                        'operation'   => 'delete',
                        'http_status' => 204,
                    ]);
                } else {
                    $data = array_merge($data, [
                        'success'     => true,
                        'operation'   => 'update',
                        'http_status' => 204,
                    ]);
                }
            } else {
                $full_data = json_decode((string) $response->getBody(), true);
                if (!is_array($full_data)) {
                    $data = array_merge($data, ['message' => $full_data]);
                } elseif (count($full_data) > 1) {
                    $data = array_merge($data, $full_data);
                } else {
                    $data['raw_sfdc_error'] = (string) $response->getBody();
                    $data = array_merge($data, current($full_data));
                }


                if ($data && isset($data['message'])) {
                    $data['message_string'] = $data['message'];
                } elseif (!$data) {
                    $data['message_string'] = (string) $response->getBody();
                }

                $data['http_status'] = $response_code;
                $data['success'] = false;

                $data = array_merge($debug_info, $data);

                $this->log('error', 'Salesforce - '.json_encode($data));
            }


            if (isset($data) && $data) {
                $this->updateAccessToken($this->oauth2Client->getAccessToken()->getToken());

                return $data;
            }
        } catch (Exception $e) {
            $data['message_string'] = $e->getMessage();
            $data['file'] = $e->getFile().':'.$e->getLine();
            $data['http_status'] = 500;
            $data['success'] = false;
            $data = array_merge($debug_info, $data);

            $this->log('error', 'Salesforce-Salesforce::call_api - '.$e->getMessage().' - '.$e->getFile().':'.$e->getLine());

            return $data;
        }

        return [];
    }

    protected function log($level, $message)
    {
        if ($this->config['salesforce.logger'] instanceof \Psr\Log\LoggerInterface && is_callable([$this->config['salesforce.logger'], $level])) {
            return call_user_func([$this->config['salesforce.logger'], $level], $message);
        } else {
            return;
        }
    }

    protected function updateAccessToken($current_access_token)
    {
        if ($current_access_token != SalesforceConfig::get('salesforce.oauth.access_token')) {
            $this->repository->store->setAccessToken($current_access_token);
            SalesforceConfig::set('salesforce.oauth.access_token', $current_access_token);
        }
    }
}
