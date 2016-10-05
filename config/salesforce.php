<?php

return [
    'api' => [
        'domain' => env('SALESFORCE_API_DOMAIN', 'na1.salesforce.com'),

        'base_uri' => env('SALESFORCE_API_BASE_URI', '/services/data/v36.0/'),

        'version' => env('SALESFORCE_API_BASE_VERSION', '36.0'),
    ],
    'oauth' => [
        'auth_type' => env('SALESFORCE_OAUTH_AUTH_TYPE', 'web_server'), //OPTIONS: web_server or jwt_web_token

        'domain' => env('SALESFORCE_OAUTH_DOMAIN', 'login.salesforce.com'),

        'authorize_uri' => env('SALESFORCE_OAUTH_AUTHORIZE_URI', '/services/oauth2/authorize'),

        'token_uri' => env('SALESFORCE_OAUTH_TOKEN_URI', '/services/oauth2/token'),

        'callback_url' => env('SALESFORCE_OAUTH_CALLBACK_URL', ''),

        'consumer_token' => env('SALESFORCE_OAUTH_CONSUMER_TOKEN', null),

        'consumer_secret' => env('SALESFORCE_OAUTH_CONSUMER_SECRET', null),

        'scopes' => [
            'api',
            'offline_access',
            'refresh_token',
        ],

        'jwt' => [
            'private_key'            => env('SALESFORCE_OAUTH_JWT_PRIVATE_KEY'),
            'private_key_passphrase' => env('SALESFORCE_OAUTH_JWT_PRIVATE_KEY_PASSPHRASE'),
            'run_as_user_name'       => env('SALESFORCE_OAUTH_JWT_RUN_AS_USER_NAME'),
        ],
    ],
    'storage_type'           => 'eloquent',
    'storage_global_user_id' => null,
    'enable_oauth_routes'    => env('SALESFORCE_ENABLE_OAUTH_ROUTES', false),
    'logger'                 => env('SALESFORCE_LOGGER_CLASS', null),
];
