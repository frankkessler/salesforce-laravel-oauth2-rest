<?php

namespace Frankkessler\Salesforce;

class Custom
{
    /**
     * @var Salesforce
     */
    private $oauth2Client;

    public function __construct($oauth2Client)
    {
        $this->oauth2Client = $oauth2Client;
    }

    public function get($uri)
    {
        $url = 'https://'.SalesforceConfig::get('salesforce.api.domain').'/services/apexrest/'.$uri;

        return $this->oauth2Client->rawgetRequest($url);
    }

    public function post($uri, $data)
    {
        $url = 'https://'.SalesforceConfig::get('salesforce.api.domain').'/services/apexrest/'.$uri;

        return $this->oauth2Client->rawPostRequest($url, $data);
    }
}
