<?php
namespace Frankkessler\Salesforce;

use Config;

class SalesforceConfig{

    private static $config;

    public static function get($key=null)
    {
        if(!self::$config){
            self::$config = self::getInitialConfig();
        }

        if(is_null($key)){
            return self::$config;
        }elseif(isset(self::$config[$key])){
            return self::$config[$key];
        }
        return '';
    }

    public static function set($key, $value)
    {
        if(!self::$config){
            self::$config = self::getInitialConfig();
        }
        self::$config[$key] = $value;
    }

    public static function setAll($config)
    {
        self::$config = $config;
    }

    public static function setInitialConfig($config=[])
    {
        if(!self::$config) {
            self::$config = self::getInitialConfig();
        }

        if ($config && !empty($config) && is_array($config)) {
            self::$config = array_replace(self::$config, $config);
        }
    }

    protected static function getInitialConfig()
    {
        $config = Config::get('salesforce');
        $config = ['salesforce'=>$config];
        return array_dot($config);
    }
}