<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Services\Platform;

$platform = new Platform();
$results = $platform->analyzeMarket(10);
$path = BASE_PATH . '/storage/logs/scan-' . date('Ymd-His') . '.json';
file_put_contents($path, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo 'Tarama tamamlandı: ' . $path . PHP_EOL;
