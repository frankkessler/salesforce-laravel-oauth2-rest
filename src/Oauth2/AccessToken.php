<?php

namespace Frankkessler\Salesforce\Oauth2;


class RefreshToken{
    /**
     * @param $service_authorization_url - Oauth2 server authorization url
     * @param $config - Requires client_id, client_secret, and refresh_token
     * @return string - Authorization Url
     */
    public static function getAccessTokenWithAuthorizationCode($service_token_url, $config){
        $config = array_merge(self::getDefaultConfig(), $config);

        return $service_token_url.'?grant_type='.$config['grant_type'].'&client_id='.$config['client_id'].'&client_secret='.$config['client_secret'].'&refresh_token='.$config['refresh_token'];
    }
    protected static function getDefaultConfig(){
        return [
            'grant_type' => 'refresh_token',
            'client_id' => '',
            'client_secret' => '',
            'refresh_token' => '',
        ];
    }

    protected function updateRefreshToken($user_id, $refreshToken){

    }
}