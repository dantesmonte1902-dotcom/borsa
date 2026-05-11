<?php

namespace App\Alerts;

final class AlertManager
{
    /** @param NotificationChannelInterface[] $channels */
    public function __construct(private readonly array $channels)
    {
    }

    public function send(array $payload): array
    {
        $results = [];
        foreach ($this->channels as $channel) {
            $results[$channel::class] = $channel->send($payload);
        }

        return $results;
    }
}
