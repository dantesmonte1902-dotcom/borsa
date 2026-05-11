<?php

namespace App\Services;

final class CandleNormalizer
{
    public static function normalize(array $candles): array
    {
        return array_values(array_map(static function (array $candle): array {
            return [
                'time' => $candle['time'],
                'open' => (float) $candle['open'],
                'high' => (float) $candle['high'],
                'low' => (float) $candle['low'],
                'close' => (float) $candle['close'],
                'volume' => (float) ($candle['volume'] ?? 0),
            ];
        }, $candles));
    }
}
