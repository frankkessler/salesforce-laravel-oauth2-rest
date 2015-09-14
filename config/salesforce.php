<?php

return [
    'api' => [
        'domain' => env('SALESFORCE_API_DOMAIN','na1.salesforce.com'),

        'api_base_uri' => env('SALESFORCE_API_BASE_URI', '/services/data/v34.0/sobjects'),
    ],
    'oauth' => [
        'domain' => env('SALESFORCE_OAUTH_DOMAIN','login.salesforce.com'),

        'callback_url' => env('SALESFORCE_OAUTH_CALLBACK_URL','/salesforce/callback'),

        'consumer_token' => env('SALESFORCE_OAUTH_CONSUMER_TOKEN',null),

        'consumer_secret' => env('SALESFORCE_OAUTH_CONSUMER_SECRET',null),

        'scopes' => [
            'api',
            'offline_access',
            'refresh_token',
        ]
    ],
];