<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;

class EmailVerification
{
    /**
     * True if this user already has an unexpired, unused verification token issued in the last 5 minutes.
     */
    public static function hasRecentRequest(int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT 1 FROM email_verifications
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
            "INSERT INTO email_verifications (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))"
        );
        $stmt->execute([$userId, $tokenHash]);

        return $token;
    }

    public static function findValidByToken(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        $stmt = Database::connection()->prepare(
            "SELECT * FROM email_verifications WHERE token_hash = ? AND used = 0 AND expires_at > NOW()"
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function invalidateAllForUser(int $userId): void
    {
        $stmt = Database::connection()->prepare("UPDATE email_verifications SET used = 1 WHERE user_id = ? AND used = 0");
        $stmt->execute([$userId]);
    }
}
