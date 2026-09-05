<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

/**
 * Admin-initiated notifications pushed to every user, or to everyone in one role.
 * Each send is logged as one row here (for a visible history) plus one row in
 * `notifications` per recipient (so it shows up in their normal inbox).
 */
class NotificationBroadcast
{
    private const TARGETABLE_ROLES = ['all', 'applicant', 'employer', 'admin'];

    public static function isValidTargetRole(string $targetRole): bool
    {
        return in_array($targetRole, self::TARGETABLE_ROLES, true);
    }

    /**
     * @return array{id:int, recipient_count:int}
     */
    public static function send(int $adminId, string $targetRole, string $message): array
    {
        $pdo = Database::connection();

        if ($targetRole === 'all') {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE is_active = 1 AND id != ?');
            $stmt->execute([$adminId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE role = ? AND is_active = 1 AND id != ?');
            $stmt->execute([$targetRole, $adminId]);
        }
        $userIds = array_map('intval', array_column($stmt->fetchAll(), 'id'));

        $insert = $pdo->prepare(
            'INSERT INTO notification_broadcasts (admin_id, target_role, message, recipient_count) VALUES (?, ?, ?, ?)'
        );
        $insert->execute([$adminId, $targetRole, $message, count($userIds)]);
        $broadcastId = (int) $pdo->lastInsertId();

        foreach ($userIds as $userId) {
            Notification::create($userId, 'admin_broadcast', $message, $broadcastId, 'notification_broadcast');
        }

        return ['id' => $broadcastId, 'recipient_count' => count($userIds)];
    }

    public static function recent(int $limit = 20): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT nb.*, u.full_name AS admin_name
             FROM notification_broadcasts nb
             JOIN users u ON u.id = nb.admin_id
             ORDER BY nb.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
