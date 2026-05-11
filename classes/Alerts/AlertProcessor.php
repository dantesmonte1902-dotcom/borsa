<?php

declare(strict_types=1);

namespace App\Alerts;

use App\Services\AlertRepository;
use App\Services\Config;
use App\Services\Platform;

final class AlertProcessor
{
    public function __construct(
        private readonly Platform $platform,
        private readonly AlertManager $manager,
        private readonly AlertStateStore $stateStore,
        private readonly ?AlertRepository $repository = null,
    ) {
    }

    public function process(array $alerts): array
    {
        $summary = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'cooldown' => 0,
            'rate_limited' => 0,
            'suppressed' => 0,
            'recovered' => 0,
            'skipped' => 0,
        ];

        $analyses = [];

        foreach ($alerts as $alert) {
            $summary['processed']++;

            $symbol = strtoupper(trim((string) ($alert['symbol'] ?? '')));
            $channel = strtolower(trim((string) ($alert['channel'] ?? '')));
            if ($symbol === '' || $channel === '') {
                $summary['skipped']++;
                continue;
            }

            try {
                $analysis = $analyses[$symbol] ??= $this->platform->analyzeSymbol($symbol);
                $evaluation = $this->evaluateAlert($alert, $analysis);
            } catch (\Throwable $throwable) {
                $summary['failed']++;
                $this->logNotification($alert, $channel, 'failed', 'Alarm değerlendirmesi başarısız: ' . $throwable->getMessage());
                continue;
            }

            $stateKey = $this->buildStateKey($alert);
            $state = $this->stateStore->getAlertState($stateKey);
            $wasActive = (bool) ($state['condition_active'] ?? false);
            $state['condition_active'] = (bool) $evaluation['matches'];
            $state['last_attempt_at'] = time();

            if (!$evaluation['matches']) {
                if ($wasActive) {
                    $summary['recovered']++;
                    $this->logNotification($alert, $channel, 'recovered', $this->buildRecoveryMessage($alert, $evaluation));
                }

                $state['last_status'] = 'recovered';
                $this->stateStore->setAlertState($stateKey, $state);
                continue;
            }

            if ($wasActive && ($state['last_status'] ?? null) === 'sent') {
                $summary['suppressed']++;
                $state['last_status'] = 'repeat_suppressed';
                $this->stateStore->setAlertState($stateKey, $state);
                $this->logNotification($alert, $channel, 'repeat_suppressed', $this->buildMessage($alert, $evaluation));
                continue;
            }

            $cooldownSeconds = $this->resolveCooldownSeconds($alert);
            $lastSentAt = (int) ($state['last_sent_at'] ?? 0);
            if ($lastSentAt > 0 && (time() - $lastSentAt) < $cooldownSeconds) {
                $summary['cooldown']++;
                $state['last_status'] = 'cooldown';
                $this->stateStore->setAlertState($stateKey, $state);
                $this->logNotification($alert, $channel, 'cooldown', $this->buildMessage($alert, $evaluation));
                continue;
            }

            if (!$this->passesRateLimit($channel)) {
                $summary['rate_limited']++;
                $state['last_status'] = 'rate_limited';
                $this->stateStore->setAlertState($stateKey, $state);
                $this->logNotification($alert, $channel, 'rate_limited', $this->buildMessage($alert, $evaluation));
                continue;
            }

            $payload = [
                'subject' => $this->buildSubject($alert),
                'message' => $this->buildMessage($alert, $evaluation),
            ];

            $success = $this->manager->sendTo($channel, $payload);
            $state['last_status'] = $success ? 'sent' : 'failed';
            if ($success) {
                $summary['sent']++;
                $state['last_sent_at'] = time();
                $this->stateStore->recordChannelAttempt($channel, (int) $state['last_sent_at']);
            } else {
                $summary['failed']++;
            }

            $this->stateStore->setAlertState($stateKey, $state);
            $this->logNotification($alert, $channel, $state['last_status'], $payload['message']);
        }

        $this->stateStore->flush();

        return $summary;
    }

    private function evaluateAlert(array $alert, array $analysis): array
    {
        $type = strtolower(trim((string) ($alert['alert_type'] ?? 'score_above')));
        $threshold = (float) ($alert['threshold_value'] ?? 0);
        $snapshot = $analysis['snapshot'] ?? [];
        $scores = $analysis['scores'] ?? [];

        return match ($type) {
            'price_above' => [
                'matches' => (float) ($snapshot['close'] ?? 0.0) >= $threshold,
                'current' => (float) ($snapshot['close'] ?? 0.0),
                'metric' => 'fiyat',
                'operator' => 'üzerine çıktı',
            ],
            'price_below' => [
                'matches' => (float) ($snapshot['close'] ?? 0.0) <= $threshold,
                'current' => (float) ($snapshot['close'] ?? 0.0),
                'metric' => 'fiyat',
                'operator' => 'altına indi',
            ],
            'volume_above' => [
                'matches' => (float) ($snapshot['volume'] ?? 0.0) >= $threshold,
                'current' => (float) ($snapshot['volume'] ?? 0.0),
                'metric' => 'hacim',
                'operator' => 'üzerine çıktı',
            ],
            'score_above', 'overall_score_above' => [
                'matches' => (float) ($scores['overall'] ?? 0.0) >= $threshold,
                'current' => (float) ($scores['overall'] ?? 0.0),
                'metric' => 'genel skor',
                'operator' => 'üzerine çıktı',
            ],
            default => throw new \RuntimeException('Desteklenmeyen alarm tipi: ' . $type),
        };
    }

    private function resolveCooldownSeconds(array $alert): int
    {
        $minutes = (int) ($alert['cooldown_minutes'] ?? 0);
        if ($minutes <= 0) {
            $minutes = (int) Config::get('alerts.defaults.cooldown_minutes', 15);
        }

        return max(1, $minutes) * 60;
    }

    private function passesRateLimit(string $channel): bool
    {
        $windowMinutes = max(1, (int) Config::get('alerts.rate_limit.window_minutes', 10));
        $windowSeconds = $windowMinutes * 60;
        $channelLimit = (int) Config::get('alerts.rate_limit.channels.' . $channel, Config::get('alerts.rate_limit.max_per_window', 5));

        if ($channelLimit <= 0) {
            return true;
        }

        return $this->stateStore->countChannelAttempts($channel, $windowSeconds) < $channelLimit;
    }

    private function buildStateKey(array $alert): string
    {
        if (isset($alert['id'])) {
            return 'alert:' . (string) $alert['id'];
        }

        return 'alert:' . md5(json_encode([
            $alert['symbol'] ?? '',
            $alert['alert_type'] ?? '',
            $alert['threshold_value'] ?? '',
            $alert['channel'] ?? '',
        ]));
    }

    private function buildSubject(array $alert): string
    {
        $symbol = strtoupper(trim((string) ($alert['symbol'] ?? 'BIST')));
        return $symbol . ' alarmı';
    }

    private function buildMessage(array $alert, array $evaluation): string
    {
        $symbol = strtoupper(trim((string) ($alert['symbol'] ?? '')));
        $threshold = (float) ($alert['threshold_value'] ?? 0);

        return sprintf(
            '%s | %s %.2f seviyesinin %s | Güncel değer: %.2f',
            $symbol,
            ucfirst((string) $evaluation['metric']),
            $threshold,
            $evaluation['operator'],
            (float) ($evaluation['current'] ?? 0)
        );
    }

    private function buildRecoveryMessage(array $alert, array $evaluation): string
    {
        $symbol = strtoupper(trim((string) ($alert['symbol'] ?? '')));

        return sprintf(
            '%s | %s alarm koşulu normale döndü | Güncel değer: %.2f',
            $symbol,
            ucfirst((string) $evaluation['metric']),
            (float) ($evaluation['current'] ?? 0)
        );
    }

    private function logNotification(array $alert, string $channel, string $status, string $message): void
    {
        if ($this->repository === null) {
            return;
        }

        try {
            $this->repository->logNotification(
                isset($alert['id']) ? (int) $alert['id'] : null,
                $channel,
                $status,
                $message
            );
        } catch (\Throwable) {
        }
    }
}
