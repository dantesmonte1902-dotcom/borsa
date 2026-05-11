<?php

namespace App\Services;

use PDO;

final class ScannerResultRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly SymbolRepository $symbolRepository,
    ) {
    }

    public function persistMarketScan(array $results): int
    {
        $insertedCount = 0;

        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO scanner_results (symbol_id, scanner_key, score, payload, created_at)
                 VALUES (:symbol_id, :scanner_key, :score, :payload, NOW())'
            );

            foreach ($results as $result) {
                $snapshot = $result['snapshot'] ?? [];
                $symbol = (string) ($snapshot['symbol'] ?? '');
                if ($symbol === '') {
                    continue;
                }

                $symbolId = $this->symbolRepository->upsert(
                    $symbol,
                    isset($snapshot['name']) ? (string) $snapshot['name'] : null,
                    $this->extractMarket($symbol)
                );

                foreach (($result['scanners'] ?? []) as $scannerKey => $scannerResult) {
                    if (!is_array($scannerResult)) {
                        continue;
                    }

                    $statement->execute([
                        'symbol_id' => $symbolId,
                        'scanner_key' => (string) $scannerKey,
                        'score' => round((float) ($scannerResult['score'] ?? 0), 2),
                        'payload' => json_encode($scannerResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                    ]);
                    $insertedCount++;
                }
            }

            $this->pdo->commit();

            return $insertedCount;
        } catch (\Throwable $throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $throwable;
        }
    }

    private function extractMarket(string $symbol): string
    {
        $parts = explode(':', strtoupper(trim($symbol)), 2);
        return $parts[0] !== '' ? $parts[0] : 'BIST';
    }
}
