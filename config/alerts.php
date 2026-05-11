<?php

return [
    'state_file' => BASE_PATH . '/storage/cache/alerts-state.json',
    'defaults' => [
        'cooldown_minutes' => (int) (getenv('ALERT_DEFAULT_COOLDOWN_MINUTES') ?: 15),
    ],
    'rate_limit' => [
        'window_minutes' => (int) (getenv('ALERT_RATE_LIMIT_WINDOW_MINUTES') ?: 10),
        'max_per_window' => (int) (getenv('ALERT_RATE_LIMIT_MAX_PER_WINDOW') ?: 5),
        'channels' => [
            'telegram' => (int) (getenv('ALERT_RATE_LIMIT_TELEGRAM') ?: 3),
            'discord' => (int) (getenv('ALERT_RATE_LIMIT_DISCORD') ?: 3),
            'email' => (int) (getenv('ALERT_RATE_LIMIT_EMAIL') ?: 2),
            'browser' => (int) (getenv('ALERT_RATE_LIMIT_BROWSER') ?: 10),
        ],
    ],
    'legacy_broadcast' => [
        'enabled' => filter_var(getenv('ALERT_LEGACY_BROADCAST_ENABLED') ?: true, FILTER_VALIDATE_BOOL),
        'minimum_score' => (float) (getenv('ALERT_LEGACY_MINIMUM_SCORE') ?: 65),
        'channels' => ['telegram', 'discord', 'email', 'browser'],
        'cooldown_minutes' => (int) (getenv('ALERT_LEGACY_COOLDOWN_MINUTES') ?: 30),
    ],
    'telegram' => [
        'token' => getenv('TELEGRAM_BOT_TOKEN') ?: '',
        'chat_id' => getenv('TELEGRAM_CHAT_ID') ?: '',
    ],
    'discord' => [
        'webhook' => getenv('DISCORD_WEBHOOK_URL') ?: '',
    ],
    'email' => [
        'to' => getenv('ALERT_EMAIL_TO') ?: '',
        'from' => getenv('ALERT_EMAIL_FROM') ?: 'no-reply@example.com',
    ],
];
