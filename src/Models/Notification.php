<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

class Notification
{
    public static function create(int $userId, string $type, string $message, ?int $relatedId = null, ?string $relatedType = null): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO notifications (user_id, type, message, related_id, related_type) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $type, $message, $relatedId, $relatedType]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function markEmailSent(int $notificationId): void
    {
        $stmt = Database::connection()->prepare('UPDATE notifications SET email_sent = 1 WHERE id = ?');
        $stmt->execute([$notificationId]);
    }

    public static function forUser(int $userId, int $limit = 20): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function adminUserIds(): array
    {
        $stmt = Database::connection()->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1");

        return array_column($stmt->fetchAll(), 'id');
    }

    public static function markRead(int $notificationId, int $userId): void
    {
        $stmt = Database::connection()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->execute([$notificationId, $userId]);
    }

    public static function markAllRead(int $userId): void
    {
        $stmt = Database::connection()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->execute([$userId]);
    }

    public static function unreadCount(int $userId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }
}
