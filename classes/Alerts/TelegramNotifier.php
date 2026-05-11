<?php

namespace App\Alerts;

use App\Services\Config;

final class TelegramNotifier implements NotificationChannelInterface
{
    public function send(array $payload): bool
    {
        $token = Config::get('alerts.telegram.token');
        $chatId = Config::get('alerts.telegram.chat_id');
        if ($token === '' || $chatId === '') {
            return false;
        }

        $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
        $body = http_build_query([
            'chat_id' => $chatId,
            'text' => $payload['message'] ?? '',
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => 20,
            ],
        ]);

        return @file_get_contents($url, false, $context) !== false;
    }
}
