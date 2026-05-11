<?php

namespace App\Alerts;

final class AlertManager
{
    /** @var array<string, NotificationChannelInterface> */
    private array $channels;

    /** @param NotificationChannelInterface[] $channels */
    public function __construct(array $channels)
    {
        $this->channels = [];

        foreach ($channels as $key => $channel) {
            if (!$channel instanceof NotificationChannelInterface) {
                continue;
            }

            $channelKey = is_string($key) ? strtolower($key) : $this->resolveChannelKey($channel);
            $this->channels[$channelKey] = $channel;
        }
    }

    public function send(array $payload): array
    {
        $results = [];
        foreach ($this->channels as $channelKey => $channel) {
            $results[$channelKey] = $channel->send($payload);
        }

        return $results;
    }

    public function sendTo(string $channel, array $payload): bool
    {
        $channelKey = strtolower(trim($channel));
        $notifier = $this->channels[$channelKey] ?? null;

        return $notifier?->send($payload) ?? false;
    }

    private function resolveChannelKey(NotificationChannelInterface $channel): string
    {
        return match (true) {
            $channel instanceof TelegramNotifier => 'telegram',
            $channel instanceof DiscordWebhookNotifier => 'discord',
            $channel instanceof EmailNotifier => 'email',
            $channel instanceof BrowserNotifier => 'browser',
            default => strtolower($channel::class),
        };
    }
}
