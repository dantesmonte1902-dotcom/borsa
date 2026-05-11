<?php

namespace App\Scanners;

final class LowFloatScanner
{
    public function scan(array $snapshot, array $indicators): array
    {
        $freeFloat = max((float) ($snapshot['free_float_shares'] ?? 0), 1.0);
        $volume = (float) ($snapshot['volume'] ?? 0);
        $valueTraded = (float) ($snapshot['value_traded'] ?? 0);
        $marketCap = max((float) ($snapshot['market_cap'] ?? 0), 1.0);

        $boardStiffness = min(100.0, (1 / max($freeFloat / 1_000_000, 0.2)) * 15);
        $speculativePower = min(100.0, (($valueTraded + 1) / ($marketCap + 1)) * 1000);
        $compressionScore = min(100.0, max(0.0, 100 - (float) ($indicators['bollinger']['width'][array_key_last($indicators['bollinger']['width'])] ?? 100)));
        $explosionPotential = min(100.0, (($volume + 1) / ($freeFloat + 1)) * 100000);

        return [
            'scanner' => 'low_float',
            'metrics' => [
                'board_stiffness' => round($boardStiffness, 2),
                'speculative_power' => round($speculativePower, 2),
                'compression_score' => round($compressionScore, 2),
                'explosion_potential' => round($explosionPotential, 2),
            ],
            'score' => round(($boardStiffness * 0.25) + ($speculativePower * 0.25) + ($compressionScore * 0.25) + ($explosionPotential * 0.25), 2),
        ];
    }
}
