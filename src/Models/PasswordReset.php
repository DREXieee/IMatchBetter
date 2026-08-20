<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;

class PasswordReset
{
    /**
     * True if this user already has an unexpired, unused reset token issued in the last 5 minutes.
     */
    public static function hasRecentRequest(int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT 1 FROM password_resets
             WHERE user_id = ? AND used = 0 AND expires_at > NOW() AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             LIMIT 1"
        );
        $stmt->execute([$userId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function create(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $stmt = Database::connection()->prepare(
            "INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))"
        );
        $stmt->execute([$userId, $tokenHash]);

        return $token;
    }

    public static function findValidByToken(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        $stmt = Database::connection()->prepare(
            "SELECT * FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > NOW()"
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function invalidateAllForUser(int $userId): void
    {
        $stmt = Database::connection()->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0");
        $stmt->execute([$userId]);
    }
}
