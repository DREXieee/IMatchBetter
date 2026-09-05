<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function create(string $email, string $password, string $role, string $fullName, ?string $phone = null): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (email, password_hash, role, full_name, phone) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $role,
            $fullName,
            $phone,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function emailExists(string $email): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([$email]);

        return (bool) $stmt->fetchColumn();
    }

    public static function registerFailedAttempt(int $userId): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT failed_attempts FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $attempts = (int) $stmt->fetchColumn() + 1;

        if ($attempts >= 5) {
            $update = $pdo->prepare(
                'UPDATE users SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?'
            );
            $update->execute([$attempts, $userId]);
        } else {
            $update = $pdo->prepare('UPDATE users SET failed_attempts = ? WHERE id = ?');
            $update->execute([$attempts, $userId]);
        }
    }

    public static function resetFailedAttempts(int $userId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?'
        );
        $stmt->execute([$userId]);
    }

    public static function updateContactInfo(int $userId, string $fullName, ?string $phone): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
        $stmt->execute([$fullName, $phone ?: null, $userId]);
    }

    public static function updatePassword(int $userId, string $newPassword): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
    }

    public static function setActive(int $userId, bool $active): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET is_active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $userId]);
    }

    public static function markEmailVerified(int $userId): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = ?');
        $stmt->execute([$userId]);
    }

    public static function isEmailVerified(int $userId): bool
    {
        $stmt = Database::connection()->prepare('SELECT email_verified_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $value = $stmt->fetchColumn();

        return $value !== null && $value !== false;
    }

    public static function all(int $limit = 50, int $offset = 0): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
