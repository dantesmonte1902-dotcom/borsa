<?php

namespace App\Indicators;

final class MACDIndicator
{
    public static function calculate(array $values, int $fast = 12, int $slow = 26, int $signal = 9): array
    {
        $fastLine = EMAIndicator::calculate($values, $fast);
        $slowLine = EMAIndicator::calculate($values, $slow);
        $macdLine = [];

        foreach ($values as $index => $_) {
            $fastValue = $fastLine[$index] ?? null;
            $slowValue = $slowLine[$index] ?? null;
            $macdLine[] = ($fastValue !== null && $slowValue !== null) ? $fastValue - $slowValue : null;
        }

        $signalLine = EMAIndicator::calculate(array_map(static fn ($item) => $item ?? 0.0, $macdLine), $signal);
        $histogram = [];
        foreach ($macdLine as $index => $value) {
            $signalValue = $signalLine[$index] ?? null;
            $histogram[] = ($value !== null && $signalValue !== null) ? $value - $signalValue : null;
        }

        return ['macd' => $macdLine, 'signal' => $signalLine, 'histogram' => $histogram];
    }
}
