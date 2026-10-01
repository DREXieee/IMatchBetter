<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

class PasswordResetRequest
{
    /**
     * True if this user already has a pending request created in the last 5 minutes —
     * prevents spamming admins with duplicate requests for the same account.
     */
    public static function hasRecentRequest(int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT 1 FROM password_reset_requests WHERE user_id = ? AND status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) LIMIT 1"
        );
        $stmt->execute([$userId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function create(int $userId): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO password_reset_requests (user_id) VALUES (?)');
        $stmt->execute([$userId]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function pending(int $limit = 50, int $offset = 0): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT prr.*, u.email, u.full_name
             FROM password_reset_requests prr
             JOIN users u ON u.id = prr.user_id
             WHERE prr.status = "pending"
             ORDER BY prr.created_at ASC
             LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT prr.*, u.email, u.full_name FROM password_reset_requests prr JOIN users u ON u.id = prr.user_id WHERE prr.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function approve(int $id, int $adminId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE password_reset_requests SET status = "approved", reviewed_by = ?, reviewed_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$adminId, $id]);
    }

    public static function reject(int $id, int $adminId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE password_reset_requests SET status = "rejected", reviewed_by = ?, reviewed_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$adminId, $id]);
    }
}
