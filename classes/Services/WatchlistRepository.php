<?php

namespace App\Services;

use PDO;

final class WatchlistRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT symbol, created_at FROM watchlists WHERE user_id = :user_id ORDER BY symbol');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function add(int $userId, string $symbol): bool
    {
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO watchlists (user_id, symbol, created_at) VALUES (:user_id, :symbol, NOW())');
        return $stmt->execute(['user_id' => $userId, 'symbol' => $symbol]);
    }
}
