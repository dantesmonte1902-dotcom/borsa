<?php

namespace App\Indicators;

final class SMAIndicator
{
    public static function calculate(array $values, int $period): array
    {
        $result = [];
        $window = [];

        foreach ($values as $value) {
            $window[] = (float) $value;
            if (count($window) > $period) {
                array_shift($window);
            }
            $result[] = count($window) === $period ? array_sum($window) / $period : null;
        }

        return $result;
    }
}
