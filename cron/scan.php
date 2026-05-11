<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Services\Database;
use App\Services\Platform;
use App\Services\ScannerResultRepository;
use App\Services\SymbolRepository;

$platform = new Platform();
$results = $platform->analyzeMarket(10);
$path = BASE_PATH . '/storage/logs/scan-' . date('Ymd-His') . '.json';
file_put_contents($path, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$persistedRows = null;
$persistError = null;

try {
    $pdo = Database::connection();
    $repository = new ScannerResultRepository($pdo, new SymbolRepository($pdo));
    $persistedRows = $repository->persistMarketScan($results);
} catch (Throwable $throwable) {
    $persistError = $throwable->getMessage();
}

echo 'Tarama tamamlandı: ' . $path . PHP_EOL;

if ($persistedRows !== null) {
    echo 'Veritabanına yazılan scanner sonucu: ' . $persistedRows . PHP_EOL;
}

if ($persistError !== null) {
    fwrite(STDERR, 'Veritabanı kaydı atlandı: ' . $persistError . PHP_EOL);
}
