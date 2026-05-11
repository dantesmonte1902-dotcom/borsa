<?php

namespace App\Alerts;

interface NotificationChannelInterface
{
    public function send(array $payload): bool;
}
