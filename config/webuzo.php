<?php

return [
    'host' => env('WEBUZO_HOST', ''),
    'scheme' => env('WEBUZO_SCHEME', 'https'),
    'admin_port' => (int) env('WEBUZO_ADMIN_PORT', 2005),
    'enduser_port' => (int) env('WEBUZO_ENDUSER_PORT', 2003),
    'response' => env('WEBUZO_RESPONSE', 'json'),
    'timeout' => (int) env('WEBUZO_TIMEOUT', 30),
    'connect_timeout' => (int) env('WEBUZO_CONNECT_TIMEOUT', 10),
    'ssl_verify' => env('WEBUZO_SSL_VERIFY', false),
    'logging' => env('WEBUZO_LOGGING', false),
    'max_retries' => (int) env('WEBUZO_MAX_RETRIES', 3),
    'retry_delay' => (int) env('WEBUZO_RETRY_DELAY', 1000),
    'rate_limiting' => env('WEBUZO_RATE_LIMITING', false),
    'rate_limit_max' => (int) env('WEBUZO_RATE_LIMIT_MAX', 60),
    'rate_limit_window' => (int) env('WEBUZO_RATE_LIMIT_WINDOW', 60),

    'auth' => [
        'method' => env('WEBUZO_AUTH_METHOD', 'api_key'),
        'api_user' => env('WEBUZO_API_USER', 'root'),
        'api_key' => env('WEBUZO_API_KEY', ''),
        'username' => env('WEBUZO_USERNAME', ''),
        'password' => env('WEBUZO_PASSWORD', ''),
    ],

    'netdata' => [
        'port'    => (int) env('NETDATA_PORT', 19999),
        'scheme'  => env('NETDATA_SCHEME', 'http'),
        'timeout' => (int) env('NETDATA_TIMEOUT', 10),
    ],
];
