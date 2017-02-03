<?php

namespace Frankkessler\Salesforce;

use Exception;
use Frankkessler\Guzzle\Oauth2\GrantType\JwtBearer;
use Frankkessler\Guzzle\Oauth2\GrantType\RefreshToken;
use Frankkessler\Guzzle\Oauth2\Oauth2Client;
use Frankkessler\Salesforce\Repositories\TokenRepository;

class Salesforce
{
    public $oauth2Client;

    protected $config_local;

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

        $this->config_local = SalesforceConfig::get();

        $this->repository = new TokenRepository();

        if (isset($this->config_local['base_uri'])) {
            $base_uri = $this->config_local['base_uri'];
        } else {
            $base_uri = 'https://'.SalesforceConfig::get('salesforce.api.domain').SalesforceConfig::get('salesforce.api.base_uri');
        }

        $client_config = [
            'base_uri' => $base_uri,
            'auth'     => 'oauth2',
        ];

        //allow for override of default oauth2 handler
        if (isset($this->config_local['handler'])) {
            $client_config['handler'] = $this->config_local['handler'];
        }

        if (!$this->oauth2Client) {
            $this->oauth2Client = new Oauth2Client($client_config);
        }

        $this->setupOauthClient();
    }

    public function setupOauthClient()
    {
        //If access_token or refresh_token are NOT supplied through constructor, pull them from the repository
        if (!SalesforceConfig::get('salesforce.oauth.access_token') || !SalesforceConfig::get('salesforce.oauth.refresh_token')) {
            $token_record = $this->repository->store->getTokenRecord();
            SalesforceConfig::set('salesforce.oauth.access_token', $token_record->access_token);
            SalesforceConfig::set('salesforce.oauth.refresh_token', $token_record->refresh_token);
        }

        $access_token = SalesforceConfig::get('salesforce.oauth.access_token');
        $refresh_token = SalesforceConfig::get('salesforce.oauth.refresh_token');

        //Set access token and refresh token in Guzzle oauth client
        $this->oauth2Client->setAccessToken($access_token, $access_token_type = 'Bearer');

        if (isset($this->config_local['token_url'])) {
            $token_url = $this->config_local['token_url'];
        } else {
            $token_url = 'https://'.SalesforceConfig::get('salesforce.oauth.domain').SalesforceConfig::get('salesforce.oauth.token_uri');
        }

        if (SalesforceConfig::get('salesforce.oauth.auth_type') == 'jwt_web_token') {
            $jwt_token_config = [
                'client_id'                  => SalesforceConfig::get('salesforce.oauth.consumer_token'),
                'client_secret'              => SalesforceConfig::get('salesforce.oauth.consumer_secret'),
                'token_url'                  => $token_url,
                'auth_location'              => 'body',
                'jwt_private_key'            => SalesforceConfig::get('salesforce.oauth.jwt.private_key'),
                'jwt_private_key_passphrase' => SalesforceConfig::get('salesforce.oauth.jwt.private_key_passphrase'),
                'jwt_algorithm'              => 'RS256',
                'jwt_payload'                => [
                    'sub' => SalesforceConfig::get('salesforce.oauth.jwt.run_as_user_name'),
                    'aud' => 'https://'.SalesforceConfig::get('salesforce.oauth.domain'),
                ],
            ];
            $grantType = new JwtBearer($jwt_token_config);
            $this->oauth2Client->setGrantType($grantType);
        } else {  //web_server is default auth type
            $this->oauth2Client->setRefreshToken($refresh_token);

            $refresh_token_config = [
                'client_id'     => SalesforceConfig::get('salesforce.oauth.consumer_token'),
                'client_secret' => SalesforceConfig::get('salesforce.oauth.consumer_secret'),
                'refresh_token' => $refresh_token,
                'token_url'     => $token_url,
                'auth_location' => 'body',
            ];
            $this->oauth2Client->setRefreshTokenGrantType(new RefreshToken($refresh_token_config));
        }
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

        $array_result = array_replace($result->toArray(), json_decode(json_encode($result->sobject), true));

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
            $this->bulk_api = new Bulk($this->config_local);
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

            $format = 'json';
            if (isset($options['format'])) {
                $format = strtolower($options['format']);
                unset($options['format']);
            }

            //required so csv return matches json return when creating new records
            $lowerCaseHeaders = true;
            if (isset($options['lowerCaseHeaders'])) {
                $lowerCaseHeaders = $options['lowerCaseHeaders'];
                unset($options['lowerCaseHeaders']);
            }

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

            if ($format == 'xml') {
                $xml = simplexml_load_string((string) $response->getBody(), null, LIBXML_NOCDATA);
                $json = json_encode($xml);
                $response_array = json_decode($json, true);
            } elseif ($format == 'csv') {
                $response_array = csvToArray((string) $response->getBody(), $lowerCaseHeaders);
            } else {
                $response_array = json_decode((string) $response->getBody(), true);
            }

            if ($response_code == 200) {
                if (is_array($response_array)) {
                    $data = array_replace($data, $response_array);
                }

                $data['success'] = true;
                $data['http_status'] = 200;
            } elseif ($response_code == 201) {
                if (is_array($response_array)) {
                    $data = array_replace($data, $response_array);
                }
                $data['operation'] = 'create';
                $data['success'] = true;
                $data['http_status'] = 201;

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
                if (!is_array($response_array)) {
                    $data = array_merge($data, ['message' => $response_array]);
                } elseif (count($response_array) > 1) {
                    $data = array_merge($data, $response_array);
                } else {
                    $data['raw_sfdc_error'] = (string) $response->getBody();
                    $data = array_merge($data, current($response_array));
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
        if ($this->config_local['salesforce.logger'] instanceof \Psr\Log\LoggerInterface && is_callable([$this->config_local['salesforce.logger'], $level])) {
            return call_user_func([$this->config_local['salesforce.logger'], $level], $message);
        } else {
            return;
        }
    }

    protected function updateAccessToken($current_access_token)
    {
        if ($current_access_token != SalesforceConfig::get('salesforce.oauth.access_token') && SalesforceConfig::get('salesforce.oauth.auth_type') != 'jwt_web_token') {
            $this->repository->store->setAccessToken($current_access_token);
            SalesforceConfig::set('salesforce.oauth.access_token', $current_access_token);
        }
    }
}
