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

    'auth' => [
        'method' => env('WEBUZO_AUTH_METHOD', 'api_key'),
        'api_user' => env('WEBUZO_API_USER', 'root'),
        'api_key' => env('WEBUZO_API_KEY', ''),
        'username' => env('WEBUZO_USERNAME', ''),
        'password' => env('WEBUZO_PASSWORD', ''),
    ],
];
