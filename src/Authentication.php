<?php

namespace Frankkessler\Salesforce;

use Frankkessler\Guzzle\Oauth2\GrantType\AuthorizationCode;
use Frankkessler\Guzzle\Oauth2\GrantType\RefreshToken;
use Frankkessler\Guzzle\Oauth2\Oauth2Client;
use Frankkessler\Guzzle\Oauth2\Utilities;
use Frankkessler\Salesforce\Repositories\TokenRepository;

class Authentication
{
    public static function returnAuthorizationLink()
    {
        $service_authorization_url = 'https://'.SalesforceConfig::get('salesforce.oauth.domain').SalesforceConfig::get('salesforce.oauth.authorize_uri');

        $oauth_config = [
            'client_id'    => SalesforceConfig::get('salesforce.oauth.consumer_token'),
            'redirect_uri' => SalesforceConfig::get('salesforce.oauth.callback_url'),
            'scope'        => SalesforceConfig::get('salesforce.oauth.scopes'),
        ];

        return '<a href="'.Utilities::getAuthorizationUrl($service_authorization_url, $oauth_config).'">Login to Salesforce</a>';
    }

    public static function processAuthenticationCode($code, $options = [])
    {
        $repository = new TokenRepository();

        $base_uri = 'https://'.SalesforceConfig::get('salesforce.api.domain').SalesforceConfig::get('salesforce.api.base_uri');

        $oauth2Client = new Oauth2Client(array_replace([
            'base_uri' => $base_uri,
        ], $options));

        $authorization_config = [
            'code'          => $code,
            'client_id'     => SalesforceConfig::get('salesforce.oauth.consumer_token'),
            'client_secret' => SalesforceConfig::get('salesforce.oauth.consumer_secret'),
            'redirect_uri'  => SalesforceConfig::get('salesforce.oauth.callback_url'),
            'token_url'     => 'https://'.SalesforceConfig::get('salesforce.oauth.domain').SalesforceConfig::get('salesforce.oauth.token_uri'),
            'auth_location' => 'body',
        ];
        $oauth2Client->setGrantType(new AuthorizationCode($authorization_config));

        $refresh_token = '';
        if ($refresh_token) {
            $refresh_config = [
                'refresh_token' => $refresh_token,
                'client_id'     => SalesforceConfig::get('salesforce.oauth.consumer_token'),
                'client_secret' => SalesforceConfig::get('salesforce.oauth.consumer_secret'),
            ];
            $oauth2Client->setRefreshTokenGrantType(new RefreshToken($refresh_config));
        }

        $access_token = $oauth2Client->getAccessToken();

        $repository->store->setTokenRecord($access_token);

        return 'Token record set successfully';
    }
}
