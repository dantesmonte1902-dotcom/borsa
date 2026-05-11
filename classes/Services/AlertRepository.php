<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class AlertRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listActive(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, user_id, symbol, alert_type, threshold_value, channel, cooldown_minutes
             FROM alerts
             WHERE is_active = 1
             ORDER BY id ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function logNotification(?int $alertId, string $channel, string $status, string $message): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notification_logs (alert_id, channel, status, message, created_at)
             VALUES (:alert_id, :channel, :status, :message, NOW())'
        );

        $stmt->execute([
            'alert_id' => $alertId,
            'channel' => $channel,
            'status' => $status,
            'message' => $message,
        ]);
    }

    public function logCron(string $jobName, string $status, ?string $message = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cron_logs (job_name, status, message, created_at)
             VALUES (:job_name, :status, :message, NOW())'
        );

        $stmt->execute([
            'job_name' => $jobName,
            'status' => $status,
            'message' => $message,
        ]);
    }
}
