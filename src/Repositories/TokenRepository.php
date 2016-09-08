<?php

namespace Frankkessler\Salesforce\Repositories;

use Frankkessler\Salesforce\Repositories\Eloquent\TokenEloquentRepository;
use Frankkessler\Salesforce\SalesforceConfig;

class TokenRepository
{
    public function __construct($config = [])
    {
        $this->store = $this->setStore($config);
    }

    /**
     * @param array $config
     *
     * @return TokenRepositoryInterface
     */
    public function setStore($config = [])
    {
        $store_name = SalesforceConfig::get('salesforce.storage_type');

        return $this->{'create'.ucfirst($store_name).'Driver'}($config);
    }

    public function createEloquentDriver($config = [])
    {
        return new TokenEloquentRepository();
    }
}
