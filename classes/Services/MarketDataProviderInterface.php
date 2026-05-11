<?php

namespace App\Services;

interface MarketDataProviderInterface
{
    public function listSymbols(): array;

    public function fetchQuoteSnapshot(string $symbol): array;

    public function fetchHistoricalCandles(string $symbol, string $range = '1y', string $interval = '1d'): array;
}
