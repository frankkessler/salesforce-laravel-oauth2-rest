<?php
namespace Frankkessler\Salesforce;

use Config;

class SalesforceConfig{

    private static $config;

    public function __construct($config=[]){
        if($config && !empty($config)){
            self::$config = $config;
        }
    }

    public static function get($key){
        if(isset(self::$config[$key])){
            return self::$config[$key];
        }else{
            if(class_exists('Config')){
                return Config::get($key);
            }
        }
        return '';
    }

    public static function set($key, $value){
        self::$config[$key] = $value;
    }

    public static function setAll($config){
        self::$config = $config;
    }
}