<?php

namespace App\Scanners;

final class DipScanner
{
    public function scan(array $snapshot, array $candles, array $indicators): array
    {
        $closes = array_column($candles, 'close');
        $volumes = array_column($candles, 'volume');
        $currentPrice = (float) ($snapshot['close'] ?? end($closes) ?: 0);
        $yearLow = min($closes);
        $distance = $yearLow > 0 ? (($currentPrice - $yearLow) / $yearLow) * 100 : 0;
        $rsi = (float) ($indicators['rsi_14'][array_key_last($indicators['rsi_14'])] ?? 0);
        $macd = (float) ($indicators['macd']['macd'][array_key_last($indicators['macd']['macd'])] ?? 0);
        $signal = (float) ($indicators['macd']['signal'][array_key_last($indicators['macd']['signal'])] ?? 0);
        $ema20 = (float) ($indicators['ema_20'][array_key_last($indicators['ema_20'])] ?? 0);
        $ema50 = (float) ($indicators['ema_50'][array_key_last($indicators['ema_50'])] ?? 0);
        $bbWidth = (float) ($indicators['bollinger']['width'][array_key_last($indicators['bollinger']['width'])] ?? 0);
        $recentVolumes = array_slice($volumes, -20);
        $avgVolume = array_sum($recentVolumes) / max(count($recentVolumes), 1);
        $volumeExpansion = $avgVolume > 0 ? (($snapshot['volume'] ?? 0) / $avgVolume) * 100 : 0;

        $recoveryProbability = min(100.0, max(0.0, 40 - $distance) + max(0.0, $rsi - 35) + ($macd > $signal ? 20 : 0));
        $riskScore = min(100.0, max(0.0, $distance * 1.5 + ($bbWidth < 12 ? 10 : 0)));
        $technicalStrength = min(100.0, ($rsi * 0.4) + ($macd > $signal ? 25 : 10) + ($currentPrice > $ema20 ? 15 : 0) + ($ema20 > $ema50 ? 20 : 5));

        return [
            'scanner' => 'dip_recovery',
            'metrics' => [
                'distance_to_year_low_pct' => round($distance, 2),
                'risk_score' => round($riskScore, 2),
                'recovery_probability' => round($recoveryProbability, 2),
                'technical_strength' => round($technicalStrength, 2),
                'volume_expansion_pct' => round($volumeExpansion, 2),
            ],
            'score' => round(($recoveryProbability * 0.35) + ($technicalStrength * 0.35) + ((100 - $riskScore) * 0.30), 2),
        ];
    }
}
