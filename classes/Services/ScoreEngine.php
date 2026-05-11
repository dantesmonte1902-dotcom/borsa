<?php

namespace App\Services;

final class ScoreEngine
{
    public function calculate(array $scannerResults): array
    {
        $weights = Config::get('scoring.weights', []);
        $technical = (float) ($scannerResults['dip_recovery']['metrics']['technical_strength'] ?? 0);
        $riskControl = max(0.0, 100 - (float) ($scannerResults['dip_recovery']['metrics']['risk_score'] ?? 100));
        $speculative = (float) ($scannerResults['low_float']['metrics']['speculative_power'] ?? 0);
        $trend = (float) ($scannerResults['pattern']['score'] ?? 0);
        $rebound = (float) ($scannerResults['dip_recovery']['metrics']['recovery_probability'] ?? 0);
        $volume = (float) ($scannerResults['volume_burst']['metrics']['money_flow_score'] ?? 0);

        $overall = ($technical * ($weights['technical'] ?? 0))
            + ($riskControl * ($weights['risk'] ?? 0))
            + ($speculative * ($weights['speculative'] ?? 0))
            + ($trend * ($weights['trend'] ?? 0))
            + ($rebound * ($weights['rebound'] ?? 0))
            + ($volume * ($weights['volume'] ?? 0));

        return [
            'technical' => round($technical, 2),
            'risk' => round($riskControl, 2),
            'speculative' => round($speculative, 2),
            'trend' => round($trend, 2),
            'rebound' => round($rebound, 2),
            'volume' => round($volume, 2),
            'overall' => round(min(100, max(0, $overall)), 2),
        ];
    }
}
