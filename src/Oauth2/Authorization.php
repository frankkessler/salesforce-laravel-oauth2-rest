<?php

namespace Frankkessler\Salesforce\Oauth2;

class Authorization{
    /**
     * @param $service_authorization_url - Oauth2 server authorization url
     * @param $config - Requires client_id, redirect_uri, and scope
     * @return string - Authorization Url
     */
    public static function getAuthorizationUrl($service_authorization_url, $config){
        $config = array_merge(self::getDefaultConfig(), $config);

        $scope_string = (is_array($config['scope']))?implode('+',$config['scope']):$config['scope'];

        return $service_authorization_url.'?response_type='.$config['response_type'].'&access_type='.$config['access_type'].'&client_id='.$config['client_id'].'&redirect_uri='.urlencode($config['redirect_uri']).'&scope='.$scope_string;
    }
    protected static function getDefaultConfig(){
        return [
            'response_type' => 'code',
            'access_type' => 'offline',
            'client_id' => '',
            'redirect_uri' => '',
            'scope' => [],
        ];
    }
}