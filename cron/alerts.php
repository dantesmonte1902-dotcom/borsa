<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Alerts\AlertManager;
use App\Alerts\BrowserNotifier;
use App\Alerts\DiscordWebhookNotifier;
use App\Alerts\EmailNotifier;
use App\Alerts\TelegramNotifier;
use App\Services\Platform;

$platform = new Platform();
$results = $platform->analyzeMarket(3);
$top = $results[0] ?? null;
if ($top === null) {
    echo "Alarm gönderilecek veri bulunamadı." . PHP_EOL;
    return;
}

$message = sprintf(
    "%s | Genel Skor: %s | %s",
    $top['snapshot']['symbol'],
    $top['scores']['overall'],
    implode(' ', $top['comments'])
);

$manager = new AlertManager([
    new TelegramNotifier(),
    new DiscordWebhookNotifier(),
    new EmailNotifier(),
    new BrowserNotifier(),
]);

print_r($manager->send(['subject' => 'Borsa Pulse Alarmı', 'message' => $message]));
