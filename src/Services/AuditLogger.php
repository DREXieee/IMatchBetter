<?php

namespace IMatchBetter\Services;

use IMatchBetter\Config\Database;

class AuditLogger
{
    public static function log(int $adminId, string $actionType, string $targetType, int $targetId, ?string $notes = null): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, notes) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$adminId, $actionType, $targetType, $targetId, $notes]);
    }

    public static function recent(int $limit = 100): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT aa.*, u.full_name AS admin_name
             FROM admin_actions aa
             JOIN users u ON u.id = aa.admin_id
             ORDER BY aa.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
