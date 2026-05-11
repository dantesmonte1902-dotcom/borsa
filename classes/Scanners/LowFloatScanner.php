<?php

namespace App\Scanners;

final class LowFloatScanner
{
    private const FLOAT_SHARE_UNIT = 1000000;
    private const MIN_FLOAT_RATIO = 0.2;
    private const BOARD_STIFFNESS_MULTIPLIER = 15;

    public function scan(array $snapshot, array $indicators): array
    {
        $freeFloat = max((float) ($snapshot['free_float_shares'] ?? 0), 1.0);
        $volume = (float) ($snapshot['volume'] ?? 0);
        $valueTraded = (float) ($snapshot['value_traded'] ?? 0);
        $marketCap = max((float) ($snapshot['market_cap'] ?? 0), 1.0);

        $boardStiffness = min(100.0, (1 / max($freeFloat / self::FLOAT_SHARE_UNIT, self::MIN_FLOAT_RATIO)) * self::BOARD_STIFFNESS_MULTIPLIER);
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
