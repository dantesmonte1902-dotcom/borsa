<?php

namespace App\Indicators;

final class RSIIndicator
{
    public static function calculate(array $values, int $period = 14): array
    {
        $result = array_fill(0, count($values), null);
        if (count($values) <= $period) {
            return $result;
        }

        $gains = [];
        $losses = [];
        for ($i = 1, $count = count($values); $i < $count; $i++) {
            $change = (float) $values[$i] - (float) $values[$i - 1];
            $gains[] = max($change, 0);
            $losses[] = abs(min($change, 0));
        }

        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;
        $result[$period] = $avgLoss === 0.0 ? 100.0 : 100 - (100 / (1 + ($avgGain / $avgLoss)));

        for ($i = $period + 1, $count = count($values); $i < $count; $i++) {
            $gain = $gains[$i - 1] ?? 0;
            $loss = $losses[$i - 1] ?? 0;
            $avgGain = (($avgGain * ($period - 1)) + $gain) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $loss) / $period;
            $result[$i] = $avgLoss === 0.0 ? 100.0 : 100 - (100 / (1 + ($avgGain / $avgLoss)));
        }

        return $result;
    }
}
