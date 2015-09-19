<?php

namespace Frankkessler\Salesforce\Repositories;

use CommerceGuys\Guzzle\Oauth2\AccessToken;

interface TokenRepositoryInterface
{
    public function getAccessToken($user_id=null);

    public function getRefreshToken($user_id=null);

    public function setAccessToken($access_token, $user_id=null);

    public function setRefreshToken($refresh_token, $user_id=null);

    public function getTokenRecord($user_id=null);

    public function setTokenRecord(AccessToken $token, $user_id=null);

}