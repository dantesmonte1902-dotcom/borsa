<?php

namespace App\Scanners;

final class VolumeBurstScanner
{
    public function scan(array $snapshot, array $candles, array $indicators): array
    {
        $currentVolume = (float) ($snapshot['volume'] ?? 0);
        $averageVolume = (float) ($indicators['volume_sma_20'][array_key_last($indicators['volume_sma_20'])] ?? 0);
        $price = (float) ($snapshot['close'] ?? 0);
        $recentCandles = array_values($candles);
        $previousCandle = $recentCandles[count($recentCandles) - 2] ?? end($recentCandles) ?: ['close' => $price];
        $previousClose = (float) ($previousCandle['close'] ?? $price);
        $atr = (float) ($indicators['atr_14'][array_key_last($indicators['atr_14'])] ?? 0);

        $volumeRatio = $averageVolume > 0 ? $currentVolume / $averageVolume : 0;
        $priceMove = $previousClose > 0 ? (($price - $previousClose) / $previousClose) * 100 : 0;
        $moneyFlowScore = min(100.0, ($volumeRatio * 25) + max(0.0, $priceMove * 8));
        $quietAccumulation = min(100.0, ($atr < max($price * 0.025, 0.01) ? 40 : 15) + ($volumeRatio > 1.5 ? 35 : 10));

        return [
            'scanner' => 'volume_burst',
            'metrics' => [
                'volume_ratio' => round($volumeRatio, 2),
                'price_move_pct' => round($priceMove, 2),
                'money_flow_score' => round($moneyFlowScore, 2),
                'quiet_accumulation' => round($quietAccumulation, 2),
            ],
            'score' => round(($moneyFlowScore * 0.5) + ($quietAccumulation * 0.5), 2),
        ];
    }
}
