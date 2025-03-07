<?php

return [
    'prefix' => 'api',
    'defaults' => [
        'guard' => 'api',
    ],
    'middleware' => ['api'],
    'path' => '',
    'domain' => null,
    'scan' => [
        'enabled' => false,
    ],
    'routes' => [
        'login' => 'login',
        'refresh' => 'refresh',
        'register' => 'register',
        'logout' => 'logout',
    ],
];
