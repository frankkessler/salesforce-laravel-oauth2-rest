<?php

namespace CommerceGuys\Guzzle\Oauth2;

use CommerceGuys\Guzzle\Oauth2\GrantType\GrantTypeInterface;
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshTokenGrantTypeInterface;
use CommerceGuys\Guzzle\Oauth2\Middleware\RetryModifyRequestMiddleware;
use CommerceGuys\Guzzle\Oauth2\Exceptions\InvalidGrantException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use CommerceGuys\Guzzle\Oauth2\Oauth2Client;

class BulkClient extends Oauth2Client{

    /** @var AccessToken|null */
    protected $accessToken;
    /** @var AccessToken|null */
    protected $refreshToken;

    /** @var GrantTypeInterface */
    protected $grantType;
    /** @var RefreshTokenGrantTypeInterface */
    protected $refreshTokenGrantType;


    public function __construct($config=[]){

        $config['handler'] = $this->returnHandlers();

        parent::__construct($config);
    }

    /**
     * Set the middleware handlers for all requests using Oauth2
     *
     * @return HandlerStack|null
     */
    protected function returnHandlers(){
        // Create a handler stack that has all of the default middlewares attached
        $handler = HandlerStack::create();

        //Add the Authorization header to requests.
        $handler->push(Middleware::mapRequest(function (RequestInterface $request) {
            if ($this->getConfig('auth') == 'bulk') {
                $token = $this->getAccessToken();

                if ($token !== null) {
                    $request = $request->withHeader('X-SFDC-Session', $token->getToken());
                    return $request;
                }
            }
            return $request;
        }),'add_auth_header');

        $handler->before('add_auth_header',$this->retry_modify_request(function ($retries, RequestInterface $request, ResponseInterface $response=null, $error=null){
                if($retries > 0){
                    return false;
                }
                if($response instanceof ResponseInterface){
                    if($response->getStatusCode() == 401){
                        return true;
                    }
                }
                return false;
            },
            function(RequestInterface $request, ResponseInterface $response){
                if($response instanceof ResponseInterface){
                    if($response->getStatusCode() == 401){
                        $token = $this->acquireAccessToken();
                        $this->setAccessToken($token, 'SFDC_Session_Id');

                        $modify['set_headers']['X-SFDC-Session'] = $token->getToken();
                        return Psr7\modify_request($request, $modify);
                    }
                }
                return $request;
            }
        ));

        return $handler;
    }
}