<?php

namespace Frankkessler\Salesforce\Repositories;

interface TokenRepositoryInterface {

    public function getRefreshTokenById($user_id);

    public function setRefreshTokenById($user_id, $refresh_token);

}