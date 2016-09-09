<?php

if(!function_exists('get_object_public_vars')) {
    function get_object_public_vars($object)
    {
        return get_object_vars($object);
    }
}