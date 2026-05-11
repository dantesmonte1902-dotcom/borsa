<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Services\Platform;

$platform = new Platform();
$result = $platform->analyzeMarket(5);
file_put_contents(BASE_PATH . '/storage/cache/latest-market.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Piyasa cache yenilendi." . PHP_EOL;
