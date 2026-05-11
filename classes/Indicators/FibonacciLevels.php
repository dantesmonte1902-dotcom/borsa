<?php

namespace App\Indicators;

final class FibonacciLevels
{
    public static function calculate(float $high, float $low): array
    {
        $difference = $high - $low;
        return [
            '0.236' => $high - ($difference * 0.236),
            '0.382' => $high - ($difference * 0.382),
            '0.5' => $high - ($difference * 0.5),
            '0.618' => $high - ($difference * 0.618),
            '0.786' => $high - ($difference * 0.786),
        ];
    }
}
