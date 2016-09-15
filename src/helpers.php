<?php

if (!function_exists('get_object_public_vars')) {
    function get_object_public_vars($object)
    {
        return get_object_vars($object);
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable. Supports boolean, empty and null.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return value($default);
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;

            case 'false':
            case '(false)':
                return false;

            case 'empty':
            case '(empty)':
                return '';

            case 'null':
            case '(null)':
                return;
        }

        if (strlen($value) > 1 && \Illuminate\Support\Str::startsWith($value, '"') && \Illuminate\Support\Str::endsWith($value, '"')) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
