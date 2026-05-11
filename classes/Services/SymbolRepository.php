<?php

namespace App\Services;

use PDO;

final class SymbolRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function upsert(string $symbol, ?string $name = null, string $market = 'BIST'): int
    {
        $normalizedSymbol = strtoupper(trim($symbol));
        $normalizedName = $name !== null ? trim($name) : null;
        $normalizedMarket = strtoupper(trim($market)) ?: 'BIST';

        $stmt = $this->pdo->prepare(
            'INSERT INTO symbols (symbol, name, market)
             VALUES (:symbol, :name, :market)
             ON DUPLICATE KEY UPDATE
                name = COALESCE(VALUES(name), name),
                market = VALUES(market)'
        );

        $stmt->execute([
            'symbol' => $normalizedSymbol,
            'name' => $normalizedName !== '' ? $normalizedName : null,
            'market' => $normalizedMarket,
        ]);

        $select = $this->pdo->prepare('SELECT id FROM symbols WHERE symbol = :symbol LIMIT 1');
        $select->execute(['symbol' => $normalizedSymbol]);

        return (int) $select->fetchColumn();
    }
}
