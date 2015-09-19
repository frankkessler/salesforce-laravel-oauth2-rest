<?php

namespace Frankkessler\Salesforce\Oauth2;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Oauth2Client extends Client{

    /** @var AccessToken|null */
    protected $accessToken;
    /** @var AccessToken|null */
    protected $refreshToken;

    /** @var GrantTypeInterface */
    protected $grantType;
    /** @var RefreshTokenGrantTypeInterface */
    protected $refreshTokenGrantType;


    public function __construct($config=[]){

        //$config['handlers'] = $this->returnHandlers();

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
            if ($this->getConfig()->get('auth') == 'oauth2') {
                $token = $this->getAccessToken();
                if ($token !== null) {
                    return $request->withHeader('Authorization', 'Bearer ' . $token->getToken());
                }
            }
            return $request;
        }));

        //figure out how to re-run request and return new response
        //$handler->push($this->intercept_bad_token_response());

        return $handler;
    }

    /**
     * Retry Access Token call
     */
    function intercept_bad_token_response()
    {
        return function (callable $handler) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler) {
                $promise = $handler($request, $options);
                return $promise->then(
                    function (ResponseInterface $response) use ($request) {
                        if ($response && 401 == $response->getStatusCode()) {
                            if ($this->getConfig()->get('auth') == 'oauth2' && !$this->getConfig()->get('retried')) {
                                if ($token = $this->acquireAccessToken()) {
                                    $this->accessToken = $token;
                                    $this->getConfig()->set('retried', true);
                                    return $this->send($request);
                                }
                            }
                        }
                        return $response;
                    }
                );
            };
        };
    }

    /**
     * Get a new access token.
     *
     * @return AccessToken|null
     */
    protected function acquireAccessToken()
    {
        $accessToken = null;

        if ($this->refreshTokenGrantType) {
            // Get an access token using the stored refresh token.
            if ($this->refreshToken) {
                $this->refreshTokenGrantType->setRefreshToken($this->refreshToken->getToken());
            }
            if ($this->refreshTokenGrantType->hasRefreshToken()) {
                $accessToken = $this->refreshTokenGrantType->getToken();
            }
        }
var_dump($this->grantType);
        if (!$accessToken && $this->grantType) {
            // Get a new access token.
            $accessToken = $this->grantType->getToken();
        }

        return $accessToken ?: null;
    }

    /**
     * Get the access token.
     *
     * @return AccessToken|null Oauth2 access token
     */
    public function getAccessToken()
    {
        if ($this->accessToken && $this->accessToken->isExpired()) {
            // The access token has expired.
            $this->accessToken = null;
        }

        if (null === $this->accessToken) {
            // Try to acquire a new access token from the server.
            $this->accessToken = $this->acquireAccessToken();
            if ($this->accessToken) {
                $this->refreshToken = $this->accessToken->getRefreshToken() ?: null;
            }
        }

        return $this->accessToken;
    }

    /**
     * Get the refresh token.
     *
     * @return AccessToken|null
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * Set the access token.
     *
     * @param AccessToken|string $accessToken
     * @param string             $type
     * @param int                $expires
     */
    public function setAccessToken($accessToken, $type = null, $expires = null)
    {
        if (is_string($accessToken)) {
            $accessToken = new AccessToken($accessToken, $type, ['expires' => $expires]);
        } elseif (!$accessToken instanceof AccessToken) {
            throw new \InvalidArgumentException('Invalid access token');
        }
        $this->accessToken = $accessToken;
    }

    /**
     * Set the refresh token.
     *
     * @param AccessToken|string $refreshToken The refresh token
     */
    public function setRefreshToken($refreshToken)
    {
        if (is_string($refreshToken)) {
            $refreshToken = new AccessToken($refreshToken, 'refresh_token');
        } elseif (!$refreshToken instanceof AccessToken) {
            throw new \InvalidArgumentException('Invalid refresh token');
        }
        $this->refreshToken = $refreshToken;
    }

    public function setGrantType(GrantTypeInterface $grantType){
        $this->grantType = $grantType;
    }

    public function setRefreshTokenGrantType(RefreshTokenGrantTypeInterface $refreshTokenGrantType){
        $this->refreshTokenGrantType = $refreshTokenGrantType;
    }
}