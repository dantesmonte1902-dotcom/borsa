<?php

return [
    'name' => 'Borsa Pulse',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL),
    'timezone' => getenv('APP_TIMEZONE') ?: 'Europe/Istanbul',
    'base_url' => getenv('APP_URL') ?: '',
    'cache_path' => BASE_PATH . '/storage/cache',
    'log_path' => BASE_PATH . '/storage/logs',
    'safe_hours' => [
        'start' => (int) (getenv('MARKET_SAFE_START') ?: 10),
        'end' => (int) (getenv('MARKET_SAFE_END') ?: 18),
    ],
];
