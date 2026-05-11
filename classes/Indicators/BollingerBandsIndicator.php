<?php

namespace App\Indicators;

final class BollingerBandsIndicator
{
    public static function calculate(array $values, int $period = 20, float $deviation = 2.0): array
    {
        $middle = SMAIndicator::calculate($values, $period);
        $upper = [];
        $lower = [];
        $width = [];

        foreach ($values as $index => $value) {
            if ($index + 1 < $period || $middle[$index] === null) {
                $upper[] = null;
                $lower[] = null;
                $width[] = null;
                continue;
            }

            $slice = array_slice($values, $index - $period + 1, $period);
            $mean = (float) $middle[$index];
            $variance = array_sum(array_map(static fn ($item): float => (((float) $item - $mean) ** 2), $slice)) / $period;
            $std = sqrt($variance);
            $upperBand = $mean + ($std * $deviation);
            $lowerBand = $mean - ($std * $deviation);
            $upper[] = $upperBand;
            $lower[] = $lowerBand;
            $width[] = $mean === 0.0 ? 0.0 : (($upperBand - $lowerBand) / $mean) * 100;
        }

        return ['upper' => $upper, 'middle' => $middle, 'lower' => $lower, 'width' => $width];
    }
}
