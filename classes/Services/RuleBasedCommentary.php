<?php

namespace App\Services;

final class RuleBasedCommentary
{
    public function generate(array $snapshot, array $scannerResults, array $scoreBreakdown): array
    {
        $comments = [];
        $messages = Config::all('comments');

        if (($scannerResults['volume_burst']['metrics']['volume_ratio'] ?? 0) >= 1.5) {
            $comments[] = $messages['volume_burst'];
        }

        if (($scannerResults['dip_recovery']['metrics']['distance_to_year_low_pct'] ?? 100) <= 12) {
            $comments[] = $messages['dip_zone'];
        }

        if (($scannerResults['pattern']['metrics']['bollinger_squeeze'] ?? false) === true) {
            $comments[] = $messages['squeeze'];
        }

        if (($scannerResults['low_float']['metrics']['board_stiffness'] ?? 0) >= 60) {
            $comments[] = $messages['board_stiffness'];
        }

        if (($scoreBreakdown['overall'] ?? 0) >= 70) {
            $comments[] = $messages['strong_score'];
        }

        if ($comments === []) {
            $comments[] = sprintf($messages['neutral'], $snapshot['symbol'] ?? 'Hisse');
        }

        return $comments;
    }
}
