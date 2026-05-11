<?php

namespace App\Indicators;

final class ATRIndicator
{
    public static function calculate(array $candles, int $period = 14): array
    {
        $trueRanges = [];
        foreach ($candles as $index => $candle) {
            if ($index === 0) {
                $trueRanges[] = (float) $candle['high'] - (float) $candle['low'];
                continue;
            }

            $high = (float) $candle['high'];
            $low = (float) $candle['low'];
            $previousClose = (float) $candles[$index - 1]['close'];
            $trueRanges[] = max($high - $low, abs($high - $previousClose), abs($low - $previousClose));
        }

        return SMAIndicator::calculate($trueRanges, $period);
    }
}
