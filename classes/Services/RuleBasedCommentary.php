<?php

namespace App\Services;

final class RuleBasedCommentary
{
    public function generate(array $snapshot, array $scannerResults, array $scoreBreakdown): array
    {
        $comments = [];

        if (($scannerResults['volume_burst']['metrics']['volume_ratio'] ?? 0) >= 1.5) {
            $comments[] = 'Bu hissede ortalamanın üzerine çıkan hacim hareketi dikkat çekiyor.';
        }

        if (($scannerResults['dip_recovery']['metrics']['distance_to_year_low_pct'] ?? 100) <= 12) {
            $comments[] = 'Fiyat son 12 aylık dip bölgesine yakın ve toparlanma takibi için uygun.';
        }

        if (($scannerResults['pattern']['metrics']['bollinger_squeeze'] ?? false) === true) {
            $comments[] = 'Bollinger sıkışması yaklaşan volatilite genişlemesine işaret ediyor.';
        }

        if (($scannerResults['low_float']['metrics']['board_stiffness'] ?? 0) >= 60) {
            $comments[] = 'Düşük fiili dolaşım etkisiyle tahta sertliği yüksek görünüyor.';
        }

        if (($scoreBreakdown['overall'] ?? 0) >= 70) {
            $comments[] = 'Genel skor güçlü; teknik ve hacim bileşenleri birbirini destekliyor.';
        }

        if ($comments === []) {
            $comments[] = sprintf('%s için henüz güçlü bir senkron teknik sinyal oluşmadı.', $snapshot['symbol'] ?? 'Hisse');
        }

        return $comments;
    }
}
