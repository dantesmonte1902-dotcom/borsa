<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Alerts\AlertManager;
use App\Alerts\BrowserNotifier;
use App\Alerts\DiscordWebhookNotifier;
use App\Alerts\EmailNotifier;
use App\Alerts\TelegramNotifier;
use App\Services\Config;

$chartsDir = __DIR__ . '/charts';
$symbolsDir = __DIR__ . '/bist_hisseler';
$startHour = (int) Config::get('app.safe_hours.start', 10);
$endHour = (int) Config::get('app.safe_hours.end', 18);
$currentHour = (int) date('H');

if ($currentHour < $startHour || $currentHour > $endHour) {
    echo "Piyasa alarm penceresi dışında çalıştırıldı." . PHP_EOL;
    return;
}

$manager = new AlertManager([
    new TelegramNotifier(),
    new DiscordWebhookNotifier(),
    new EmailNotifier(),
    new BrowserNotifier(),
]);

$files = glob($symbolsDir . '/*.txt') ?: [];
foreach ($files as $file) {
    $symbol = 'BIST:' . basename($file, '.txt');
    $chartFile = $chartsDir . '/' . str_replace(':', '_', $symbol) . '.png';
    if (!is_file($chartFile)) {
        echo "Grafik bulunamadı: {$chartFile}" . PHP_EOL;
        continue;
    }

    $manager->send([
        'subject' => $symbol . ' grafik alarmı',
        'message' => $symbol . ' için güncel grafik hazır: ' . basename($chartFile),
    ]);

    echo "Alarm işlendi: {$symbol}" . PHP_EOL;
}
