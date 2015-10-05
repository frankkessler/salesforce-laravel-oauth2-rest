<?php

namespace Frankkessler\Salesforce;

use CommerceGuys\Guzzle\Oauth2\Oauth2Client;
use CommerceGuys\Guzzle\Oauth2\GrantType\AuthorizationCode;
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Oauth2\Utilities;
use Frankkessler\Salesforce\Repositories\TokenRepository;




class Salesforce{
    public function __construct($config=null){
        if($config){
            SalesforceConfig::setAll($config);
        }
        $this->repository = new TokenRepository;

        $base_uri = 'https://'.SalesforceConfig::get('salesforce.api.domain').SalesforceConfig::get('salesforce.api.base_uri');

        $this->oauth2Client = new Oauth2Client([
            'base_uri' => $base_uri,
            'auth' => 'oauth2',
        ]);

        $this->token_record = $this->repository->store->getTokenRecord();

        $this->oauth2Client->setAccessToken($this->token_record->access_token, $access_token_type='Bearer');
        $this->oauth2Client->setRefreshToken($this->token_record->refresh_token, $refresh_token_type='refresh_token');
        $refresh_token_config = [
            'client_id' => SalesforceConfig::get('salesforce.oauth.consumer_token'),
            'client_secret' => SalesforceConfig::get('salesforce.oauth.consumer_secret'),
            'refresh_token' => $this->token_record->refresh_token,
            'token_url' =>'https://'.SalesforceConfig::get('salesforce.oauth.domain').SalesforceConfig::get('salesforce.oauth.token_uri'),
            'auth_location' => 'body',
        ];
        $this->oauth2Client->setRefreshTokenGrantType(new RefreshToken($refresh_token_config));
    }

    public function getObject($id, $type){
        return $this->call_api('get','sobjects/'.$type.'/'.$id);
    }

    public function createObject($type, $data){
        return $this->call_api('post','sobjects/'.$type, [
            'http_errors' => false,
            'body' => json_encode($data),
            'headers' => [
                'Content-type' => 'application/json',
            ]
        ]);
    }

    public function updateObject($id, $type, $data){
        if(!$id && isset($data['id'])){
            $id = $data['id'];
            unset($data['id']);
        }elseif(isset($data['id'])){
            unset($data['id']);
        }

        if(!$id || !$type || !$data){
            return [];
        }

        return $this->call_api('patch', 'sobjects/'.$type.'/'.$id, [
            'http_errors' => false,
            'body' => json_encode($data),
            'headers' => [
                'Content-type' => 'application/json',
            ]
        ]);
    }

    public function deleteObject($id, $type){
        if(!$type || !$id) {
            return [];
        }
        return $this->call_api('delete', 'sobjects/'. $type . '/' . $id);
    }

    public function externalGetObject($external_field_name, $external_id, $type){
        return $this->call_api('get','sobjects/'.$type.'/'.$external_field_name.'/'.$external_id);
    }

    public function externalUpsertObject($external_field_name, $external_id, $type, $data){
        $result = $this->call_api('patch','sobjects/'.$type.'/'.$external_field_name.'/'.$external_id, [
            'http_errors' => false,
            'body' => json_encode($data),
            'headers' => [
                'Content-type' => 'application/json',
            ]
        ]);
        return $result;
    }

    public function query($query){
        return $this->call_api('get','query/?q='.urlencode($query));
    }
    public function rawGetRequest($request_string){
        return $this->call_api('get',$request_string);
    }

    protected function call_api($method, $url, $options=[], $debug_info=[]){
        try{
            if(is_null($options)){
                $options = [];
            }

            if(isset($options['body'])){
               // var_dump($options['body']);
            }

            $options['http_errors'] = false;

            $response = $this->oauth2Client->{$method}($url, $options);
            //var_dump((string)$response->getBody());
            $response_code = $response->getStatusCode();
            if($response_code == 200) {
                $data = json_decode((string)$response->getBody(), true);
            }elseif($response_code == 201){
                $data = json_decode((string)$response->getBody(), true);
                $data['operation'] = 'create';
                if(isset($data['id'])){
                    $data['Id'] = $data['id'];
                }
                unset($data['id']);
            }elseif($response_code == 204){
                if(strtolower($method)=='delete'){
                    $data = [
                        'success' => true,
                        'operation' => 'delete',
                    ];
                }else{
                    $data = [
                        'success' => true,
                        'operation' => 'update',
                    ];
                }

            }elseif($response_code == 400){
                $data = json_decode((string)$response->getBody(), true);
                $data = current($data);
                $data['success'] = false;
            }else{
                $info = json_decode((string)$response->getBody(), true);
                if(!$info){
                    $info['message'] = (string)$response->getBody();
                }
                $info['http_status'] = $response_code;
                $data['success'] = false;
                $data['error'] = array_merge($debug_info,$info);
            }


            if(isset($data) && $data) {
                $this->updateAccessToken($this->oauth2Client->getAccessToken()->getToken());
                return $data;
            }
        }catch(ClientException $e){

        }
        return [];
    }
    protected function updateAccessToken($current_access_token){
        if($current_access_token != $this->token_record->access_token) {
            $this->repository->store->setAccessToken($current_access_token);
        }
    }
}