<?php

return [
    'default' => getenv('MARKET_DATA_PROVIDER') ?: 'tradingview',
    'http' => [
        'timeout' => (int) (getenv('HTTP_TIMEOUT') ?: 20),
        'retries' => (int) (getenv('HTTP_RETRIES') ?: 2),
        'retry_delay_ms' => (int) (getenv('HTTP_RETRY_DELAY_MS') ?: 500),
        'rate_limit_per_minute' => (int) (getenv('HTTP_RATE_LIMIT_PER_MINUTE') ?: 60),
    ],
    'tradingview' => [
        'scan_url' => 'https://scanner.tradingview.com/turkey/scan',
    ],
    'yahoo' => [
        'chart_url' => 'https://query1.finance.yahoo.com/v8/finance/chart/',
    ],
    'finnhub' => [
        'base_url' => 'https://finnhub.io/api/v1',
        'token' => getenv('FINNHUB_TOKEN') ?: '',
    ],
    'kap' => [
        'base_url' => getenv('KAP_BASE_URL') ?: 'https://www.kap.org.tr',
    ],
    'investing' => [
        'base_url' => getenv('INVESTING_BASE_URL') ?: 'https://www.investing.com',
    ],
];
