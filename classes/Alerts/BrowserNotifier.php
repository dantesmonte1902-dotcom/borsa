<?php

namespace App\Alerts;

use App\Services\Config;

final class BrowserNotifier implements NotificationChannelInterface
{
    public function send(array $payload): bool
    {
        $path = Config::get('app.log_path') . '/browser-notifications.log';
        return file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND) !== false;
    }
}
