<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Alerts\AlertManager;
use App\Alerts\AlertProcessor;
use App\Alerts\AlertStateStore;
use App\Alerts\BrowserNotifier;
use App\Alerts\DiscordWebhookNotifier;
use App\Alerts\EmailNotifier;
use App\Alerts\TelegramNotifier;
use App\Services\AlertRepository;
use App\Services\Config;
use App\Services\Database;
use App\Services\Platform;

$platform = new Platform();

$manager = new AlertManager([
    'telegram' => new TelegramNotifier(),
    'discord' => new DiscordWebhookNotifier(),
    'email' => new EmailNotifier(),
    'browser' => new BrowserNotifier(),
]);

$stateStore = new AlertStateStore(Config::get('alerts.state_file'));
$alertRepository = null;
$alerts = [];
$cronError = null;

try {
    $pdo = Database::connection();
    $alertRepository = new AlertRepository($pdo);
    $alerts = $alertRepository->listActive();
} catch (Throwable $throwable) {
    $cronError = $throwable->getMessage();
}

if ($alerts === []) {
    $legacyConfig = Config::get('alerts.legacy_broadcast', []);
    if (($legacyConfig['enabled'] ?? true) === true) {
        $results = $platform->analyzeMarket(3);
        $top = $results[0] ?? null;

        if ($top !== null && (float) ($top['scores']['overall'] ?? 0.0) >= (float) ($legacyConfig['minimum_score'] ?? 65.0)) {
            $alerts = array_map(
                static fn (string $channel): array => [
                    'symbol' => (string) ($top['snapshot']['symbol'] ?? ''),
                    'alert_type' => 'score_above',
                    'threshold_value' => (float) ($legacyConfig['minimum_score'] ?? 65.0),
                    'channel' => $channel,
                    'cooldown_minutes' => (int) ($legacyConfig['cooldown_minutes'] ?? 30),
                ],
                (array) ($legacyConfig['channels'] ?? ['browser'])
            );
        }
    }
}

if ($alerts === []) {
    echo "İşlenecek aktif alarm bulunamadı." . PHP_EOL;
    if ($alertRepository !== null) {
        try {
            $alertRepository->logCron('alerts', 'idle', 'İşlenecek aktif alarm bulunamadı.');
        } catch (Throwable) {
        }
    }
    return;
}

$processor = new AlertProcessor($platform, $manager, $stateStore, $alertRepository);
$summary = $processor->process($alerts);

if ($alertRepository !== null) {
    try {
        $message = json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $alertRepository->logCron('alerts', 'ok', $message !== false ? $message : null);
    } catch (Throwable) {
    }
}

echo 'Alarm özeti: ' . json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

if ($cronError !== null) {
    fwrite(STDERR, 'Veritabanı erişimi devre dışı kaldı, legacy alarm akışı kullanıldı: ' . $cronError . PHP_EOL);
}
