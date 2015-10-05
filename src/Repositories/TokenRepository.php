<?php

namespace Frankkessler\Salesforce\Repositories;

use Frankkessler\Salesforce\SalesforceConfig;
use Frankkessler\Salesforce\Repositories\Eloquent\TokenEloquentRepository;

class TokenRepository
{
    function __construct($config=[])
    {
        $this->store = $this->setStore($config);
    }

    /**
     * @param array $config
     * @return TokenRepositoryInterface
     */

    function setStore($config=[])
    {
        $store_name = SalesforceConfig::get('salesforce.storage_type');
        return $this->{'create' . ucfirst($store_name) . 'Driver'}($config);
    }

    function createEloquentDriver($config=[])
    {
        return new TokenEloquentRepository;
    }
}