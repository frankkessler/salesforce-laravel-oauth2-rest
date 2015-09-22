<?php

namespace Frankkessler\Salesforce;

use CommerceGuys\Guzzle\Oauth2\Oauth2Client;
use CommerceGuys\Guzzle\Oauth2\GrantType\AuthorizationCode;
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Oauth2\Utilities;
use Frankkessler\Salesforce\Repositories\TokenRepository;
use Config;


class Salesforce{
    public function __construct(){
        $this->repository = new TokenRepository;

        $base_uri = 'https://'.Config::get('salesforce.api.domain').Config::get('salesforce.api.base_uri');

        $this->oauth2Client = new Oauth2Client([
            'base_uri' => $base_uri,
            'auth' => 'oauth2',
        ]);

        $this->token_record = $this->repository->store->getTokenRecord();

        $this->oauth2Client->setAccessToken($this->token_record->access_token, $access_token_type='Bearer');
        $this->oauth2Client->setRefreshToken($this->token_record->refresh_token, $refresh_token_type='refresh_token');
        $refresh_token_config = [
            'client_id' => Config::get('salesforce.oauth.consumer_token'),
            'client_secret' => Config::get('salesforce.oauth.consumer_secret'),
            'refresh_token' => $this->token_record->refresh_token,
            'token_url' =>'https://'.Config::get('salesforce.oauth.domain').Config::get('salesforce.oauth.token_uri'),
            'auth_location' => 'body',
        ];
        $this->oauth2Client->setRefreshTokenGrantType(new RefreshToken($refresh_token_config));
    }

    public function getObject($id, $type){
        return $this->call_api('get','sobjects/'.$type.'/'.$id);
    }

    public function createObject($type, $data){
        $result = $this->call_api('post','sobjects/'.$type, [
            'http_errors' => false,
            'body' => json_encode($data),
            'headers' => [
                'Content-type' => 'application/json',
            ]
        ]);

        if($result && isset($result['success']) &&  $result['success']){
            return (isset($result['id']))?$result['id']:null;
        }
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

    protected function call_api($method, $url, $options=[]){
        try{
            if(is_null($options)){
                $options = [];
            }

            $options['http_errors'] = false;

            $response = $this->oauth2Client->{$method}($url, $options);

            if($data = json_decode((string)$response->getBody(), true)){
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