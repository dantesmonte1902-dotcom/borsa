<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Services\Platform;

try {
    $platform = new Platform();
    $limit = isset($_GET['limit']) ? max(1, min(20, (int) $_GET['limit'])) : 5;

    if (isset($_GET['symbol']) && $_GET['symbol'] !== '') {
        jsonResponse($platform->analyzeSymbol((string) $_GET['symbol']));
    }

    jsonResponse(['data' => $platform->analyzeMarket($limit)]);
} catch (Throwable $throwable) {
    jsonResponse(['error' => $throwable->getMessage()], 500);
}
