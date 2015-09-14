<?php


use Config;
use Frankkessler\Salesforce\Repositories\Eloquent\TokenEloquentRepository;

class TokenRepository
{
    function __contruct()
    {
        $this->store = $this->setStore();
    }

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