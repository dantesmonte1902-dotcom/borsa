<?php

namespace App\Alerts;

use App\Services\Config;

final class EmailNotifier implements NotificationChannelInterface
{
    public function send(array $payload): bool
    {
        $to = Config::get('alerts.email.to');
        if ($to === '') {
            return false;
        }

        $subject = $payload['subject'] ?? 'Borsa Pulse Alarmı';
        $headers = 'From: ' . Config::get('alerts.email.from');

        return mail($to, $subject, $payload['message'] ?? '', $headers);
    }
}
