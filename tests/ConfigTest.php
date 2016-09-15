<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class ConfigTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function testGet()
    {
        $config = \Frankkessler\Salesforce\SalesforceConfig::get();

        $this->assertEquals('login.salesforce.com', $config['salesforce.oauth.domain']);

        \Frankkessler\Salesforce\SalesforceConfig::reset();
    }

    public function testGetDefault()
    {
        $value = \Frankkessler\Salesforce\SalesforceConfig::get('nonexistent_key','default');

        $this->assertEquals('default', $value);

        \Frankkessler\Salesforce\SalesforceConfig::reset();
    }

    public function testSet()
    {
        \Frankkessler\Salesforce\SalesforceConfig::set('nonexistent_key','default');

        $value = \Frankkessler\Salesforce\SalesforceConfig::get('nonexistent_key');

        $this->assertEquals('default', $value);

        \Frankkessler\Salesforce\SalesforceConfig::reset();
    }

    public function testSetAll()
    {
        \Frankkessler\Salesforce\SalesforceConfig::setAll([
            'nonexistent_key' => 'default',
            'nonexistent_key1' => 'default1',
        ]);

        $value = \Frankkessler\Salesforce\SalesforceConfig::get('nonexistent_key1');

        $this->assertEquals('default1', $value);

        \Frankkessler\Salesforce\SalesforceConfig::reset();
    }

    public function testSetInitialConfig()
    {
        \Frankkessler\Salesforce\SalesforceConfig::setInitialConfig([
            'nonexistent_key' => 'default',
            'nonexistent_key1' => 'default1',
        ]);

        $value = \Frankkessler\Salesforce\SalesforceConfig::get('nonexistent_key1');

        $this->assertEquals('default1', $value);

        \Frankkessler\Salesforce\SalesforceConfig::reset();
    }
}
