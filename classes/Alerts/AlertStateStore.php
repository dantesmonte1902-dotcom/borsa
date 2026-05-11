<?php

declare(strict_types=1);

namespace App\Alerts;

final class AlertStateStore
{
    private array $state;

    public function __construct(private readonly string $path)
    {
        $this->state = $this->load();
    }

    public function getAlertState(string $key): array
    {
        return $this->state['alerts'][$key] ?? [
            'condition_active' => false,
            'last_sent_at' => null,
            'last_attempt_at' => null,
            'last_status' => null,
        ];
    }

    public function setAlertState(string $key, array $alertState): void
    {
        $this->state['alerts'][$key] = $alertState;
    }

    public function countChannelAttempts(string $channel, int $windowSeconds): int
    {
        $channelKey = strtolower(trim($channel));
        $timestamps = $this->pruneTimestamps($this->state['channels'][$channelKey] ?? [], $windowSeconds);
        $this->state['channels'][$channelKey] = $timestamps;

        return count($timestamps);
    }

    public function recordChannelAttempt(string $channel, int $timestamp): void
    {
        $channelKey = strtolower(trim($channel));
        $timestamps = $this->state['channels'][$channelKey] ?? [];
        $timestamps[] = $timestamp;
        $this->state['channels'][$channelKey] = array_values(array_unique(array_map('intval', $timestamps)));
    }

    public function flush(): void
    {
        $directory = dirname($this->path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($this->path, json_encode($this->state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function load(): array
    {
        if (!is_file($this->path)) {
            return ['alerts' => [], 'channels' => []];
        }

        $decoded = json_decode((string) file_get_contents($this->path), true);

        return is_array($decoded) ? $decoded : ['alerts' => [], 'channels' => []];
    }

    private function pruneTimestamps(array $timestamps, int $windowSeconds): array
    {
        if ($windowSeconds <= 0) {
            return array_values(array_map('intval', $timestamps));
        }

        $cutoff = time() - $windowSeconds;

        return array_values(array_filter(
            array_map('intval', $timestamps),
            static fn (int $timestamp): bool => $timestamp >= $cutoff
        ));
    }
}
