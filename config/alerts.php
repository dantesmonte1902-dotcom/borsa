<?php

return [
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
