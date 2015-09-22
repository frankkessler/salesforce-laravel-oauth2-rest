<?php

return [
    'api' => [
        'domain' => env('SALESFORCE_API_DOMAIN','na1.salesforce.com'),

        'base_uri' => env('SALESFORCE_API_BASE_URI', '/services/data/v34.0/'),
    ],
    'oauth' => [
        'domain' => env('SALESFORCE_OAUTH_DOMAIN','login.salesforce.com'),

        'authorize_uri' => env('SALESFORCE_OAUTH_AUTHORIZE_URI','/services/oauth2/authorize'),

        'token_uri' => env('SALESFORCE_OAUTH_TOKEN_URI','/services/oauth2/token'),

        'callback_url' => env('SALESFORCE_OAUTH_CALLBACK_URL','https://condor.progyny.tech/salesforce/admin/callback'),

        'consumer_token' => env('SALESFORCE_OAUTH_CONSUMER_TOKEN',null),

        'consumer_secret' => env('SALESFORCE_OAUTH_CONSUMER_SECRET',null),

        'scopes' => [
            'api',
            'offline_access',
            'refresh_token',
        ]
    ],
    'storage_type' => 'eloquent',
    'storage_global_user_id' => null,
];