<?php

namespace App\Scanners;

final class PatternScanner
{
    public function scan(array $candles, array $indicators): array
    {
        $closes = array_column($candles, 'close');
        $recent = array_slice($closes, -30);
        $high = max($recent);
        $low = min($recent);
        $rangePct = $low > 0 ? (($high - $low) / $low) * 100 : 0;
        $bbWidth = (float) ($indicators['bollinger']['width'][array_key_last($indicators['bollinger']['width'])] ?? 100);
        $ema20 = (float) ($indicators['ema_20'][array_key_last($indicators['ema_20'])] ?? 0);
        $ema50 = (float) ($indicators['ema_50'][array_key_last($indicators['ema_50'])] ?? 0);
        $lastClose = (float) end($closes);

        $triangle = $rangePct < 8 && $ema20 >= $ema50;
        $squeeze = $bbWidth < 10;
        $wyckoff = $squeeze && $lastClose > $ema20;

        $score = ($triangle ? 35 : 10) + ($squeeze ? 35 : 5) + ($wyckoff ? 30 : 10);

        return [
            'scanner' => 'pattern',
            'metrics' => [
                'triangle_candidate' => $triangle,
                'bollinger_squeeze' => $squeeze,
                'wyckoff_accumulation' => $wyckoff,
                'range_pct' => round($rangePct, 2),
            ],
            'score' => round(min(100, $score), 2),
        ];
    }
}
