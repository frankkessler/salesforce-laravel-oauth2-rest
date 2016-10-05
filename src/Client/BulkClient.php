<?php

namespace Frankkessler\Salesforce\Client;

use Frankkessler\Guzzle\Oauth2\Oauth2Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BulkClient extends Oauth2Client
{
    public function __construct($config = [])
    {
        if (!isset($config['handler'])) {
            $config['handler'] = $this->returnHandlers();
        }

        parent::__construct($config);
    }

    /**
     * Set the middleware handlers for all requests using Oauth2.
     *
     * @return HandlerStack|null
     */
    public function returnHandlers()
    {
        // Create a handler stack that has all of the default middlewares attached
        $handler = HandlerStack::create();

        //Add the Authorization header to requests.
        $handler->push($this->mapRequest(), 'add_auth_header');

        $handler->before('add_auth_header', $this->modifyRequest());

        return $handler;
    }

    public function mapRequest()
    {
        return  Middleware::mapRequest(function (RequestInterface $request) {
            if ($this->getConfig('auth') == 'bulk') {
                $token = $this->getAccessToken();

                if ($token !== null) {
                    $request = $request->withHeader('X-SFDC-Session', $token->getToken());

                    return $request;
                }
            }

            return $request;
        });
    }

    public function modifyRequest()
    {
        return $this->retry_modify_request(function ($retries, RequestInterface $request, ResponseInterface $response = null, $error = null) {
            if ($retries > 0) {
                return false;
            }
            if ($response instanceof ResponseInterface) {
                if (in_array($response->getStatusCode(), [400, 401])) {
                    return true;
                }
            }

            return false;
        },
            function (RequestInterface $request, ResponseInterface $response) {
                if ($response instanceof ResponseInterface) {
                    if (in_array($response->getStatusCode(), [400, 401])) {
                        $token = $this->acquireAccessToken();
                        $this->setAccessToken($token, 'Bearer');

                        $modify['set_headers']['X-SFDC-Session'] = $token->getToken();

                        return Psr7\modify_request($request, $modify);
                    }
                }

                return $request;
            }
        );
    }
}
