<?php

namespace Frankkessler\Salesforce\Repositories;

use Frankkessler\Guzzle\Oauth2\AccessToken;

interface TokenRepositoryInterface
{
    public function setAccessToken($access_token, $user_id = null);

    public function setRefreshToken($refresh_token, $user_id = null);

    public function getTokenRecord($user_id = null);

    public function setTokenRecord(AccessToken $token, $user_id = null);
}
