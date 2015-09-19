<?php

namespace Frankkessler\Salesforce\Repositories;

use Config;
use Frankkessler\Salesforce\Repositories\Eloquent\TokenEloquentRepository;

class TokenRepository
{
    function __construct($config=[])
    {
        $this->store = $this->setStore();
    }

    /**
     * @param array $config
     * @return TokenRepositoryInterface
     */

    function setStore($config=[])
    {
        $store_name = Config::get('salesforce.storage_type');
        return $this->{'create' . ucfirst($store_name) . 'Driver'}($config);
    }

    function createEloquentDriver($config=[])
    {
        return new TokenEloquentRepository;
    }
}