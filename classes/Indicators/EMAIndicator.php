<?php

namespace App\Indicators;

final class EMAIndicator
{
    public static function calculate(array $values, int $period): array
    {
        $result = [];
        $multiplier = 2 / ($period + 1);
        $ema = null;

        foreach ($values as $index => $value) {
            $value = (float) $value;
            if ($ema === null) {
                $ema = $value;
            } else {
                $ema = (($value - $ema) * $multiplier) + $ema;
            }

            $result[] = $index + 1 >= $period ? $ema : null;
        }

        return $result;
    }
}
