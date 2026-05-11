<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Services\Config;
use App\Services\Platform;

$txtDir = __DIR__ . '/bist_hisseler';
if (!is_dir($txtDir)) {
    mkdir($txtDir, 0775, true);
}

$startHour = (int) Config::get('app.safe_hours.start', 10);
$endHour = (int) Config::get('app.safe_hours.end', 18);
$currentHour = (int) date('H');
if ($currentHour < $startHour || $currentHour > $endHour) {
    echo "Piyasa veri toplama penceresi dışında." . PHP_EOL;
    return;
}

$platform = new Platform();
$results = $platform->analyzeMarket(25);

foreach ($results as $result) {
    $symbol = str_replace('BIST:', '', $result['snapshot']['symbol']);
    $path = $txtDir . '/' . $symbol . '.txt';
    if (!is_file($path)) {
        file_put_contents($path, "time,price,high,low,volume,overall_score\n");
    }

    $line = implode(',', [
        date('Y-m-d H:i'),
        $result['snapshot']['close'],
        $result['snapshot']['high'],
        $result['snapshot']['low'],
        $result['snapshot']['volume'],
        $result['scores']['overall'],
    ]) . PHP_EOL;

    file_put_contents($path, $line, FILE_APPEND);
    echo "Kaydedildi: {$symbol}" . PHP_EOL;
}
