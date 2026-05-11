<?php

namespace App\Alerts;

use App\Services\Config;

final class DiscordWebhookNotifier implements NotificationChannelInterface
{
    public function send(array $payload): bool
    {
        $webhook = Config::get('alerts.discord.webhook');
        if ($webhook === '') {
            return false;
        }

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode(['content' => $payload['message'] ?? '']),
                'timeout' => 20,
            ],
        ];

        return @file_get_contents($webhook, false, stream_context_create($options)) !== false;
    }
}
