<?php

namespace Frankkessler\Salesforce\Controllers;

use Illuminate\Http\Request;

use App;
use Config;
use App\Http\Requests;
use Frankkessler\Salesforce\Controllers\BaseController;
use Illuminate\Support\Facades\View;
use GuzzleHttp\Client;
use CommerceGuys\Guzzle\Oauth2\Oauth2Client;
use CommerceGuys\Guzzle\Oauth2\GrantType\AuthorizationCode;
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Oauth2\Utilities;
use Frankkessler\Salesforce\Repositories\TokenRepository;
use Frankkessler\Salesforce\Salesforce;


class SalesforceController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function login_form()
    {
        $service_authorization_url = 'https://'.Config::get('salesforce.oauth.domain').Config::get('salesforce.oauth.authorize_uri');

        $config = Config::get('salesforce.oauth');

        $oauth_config = [
            'client_id' => $config['consumer_token'],
            'redirect_uri' => $config['callback_url'],
            'scope'=> $config['scopes'],
        ];

        return '<a href="'.Utilities::getAuthorizationUrl($service_authorization_url, $oauth_config).'">Login to Salesforce</a>';
    }

    public function process_authorization_callback(Request $request){
        if (!$request->has('code')){
            die;
        }
        $repository = new TokenRepository;

        $base_uri = 'https://'.Config::get('salesforce.api.domain').Config::get('salesforce.api.base_uri');

        $oauth2Client = new Oauth2Client([
            'base_uri' => $base_uri,
        ]);

        $authorization_config = [
            'code' => $request->input('code'),
            'client_id' => Config::get('salesforce.oauth.consumer_token'),
            'client_secret' => Config::get('salesforce.oauth.consumer_secret'),
            'redirect_uri' => Config::get('salesforce.oauth.callback_url'),
            'token_url' =>'https://'.Config::get('salesforce.oauth.domain').Config::get('salesforce.oauth.token_uri'),
            'auth_location' => 'body',
        ];
        $oauth2Client->setGrantType(new AuthorizationCode($authorization_config));

        $refresh_token = '';
        if($refresh_token) {
            $refresh_config = [
                'refresh_token' => $refresh_token,
                'client_id' => Config::get('salesforce.oauth.consumer_token'),
                'client_secret' => Config::get('salesforce.oauth.consumer_secret'),
            ];
            $oauth2Client->setRefreshTokenGrantType(new RefreshToken($refresh_config));
        }

        $access_token = $oauth2Client->getAccessToken();

        $repository->store->setTokenRecord($access_token);

        return 'Token record set successfully';
    }

}
