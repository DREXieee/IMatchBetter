<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

class EmployerProfile
{
    public static function findByUserId(int $userId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM employer_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();

        return $profile ?: null;
    }

    public static function create(int $userId, string $companyName, ?string $website, ?string $description): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO employer_profiles (user_id, company_name, company_website, company_description) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $companyName, $website, $description]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function isApproved(int $userId): bool
    {
        $profile = self::findByUserId($userId);

        return $profile !== null && $profile['approval_status'] === 'approved';
    }

    public static function pending(int $limit = 50, int $offset = 0): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ep.*, u.email, u.full_name
             FROM employer_profiles ep
             JOIN users u ON u.id = ep.user_id
             WHERE ep.approval_status = "pending"
             ORDER BY ep.created_at ASC
             LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function approve(int $employerProfileId, int $adminId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE employer_profiles SET approval_status = "approved", reviewed_by = ?, reviewed_at = NOW(), rejection_reason = NULL WHERE id = ?'
        );
        $stmt->execute([$adminId, $employerProfileId]);
    }

    public static function reject(int $employerProfileId, int $adminId, string $reason): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE employer_profiles SET approval_status = "rejected", reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? WHERE id = ?'
        );
        $stmt->execute([$adminId, $reason, $employerProfileId]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ep.*, u.email, u.full_name
             FROM employer_profiles ep
             JOIN users u ON u.id = ep.user_id
             WHERE ep.id = ?'
        );
        $stmt->execute([$id]);
        $profile = $stmt->fetch();

        return $profile ?: null;
    }

    public static function updateProfile(int $userId, string $companyName, ?string $website, ?string $description, ?string $logoPath = null): void
    {
        $pdo = Database::connection();

        if ($logoPath !== null) {
            $stmt = $pdo->prepare(
                'UPDATE employer_profiles SET company_name = ?, company_website = ?, company_description = ?, logo_path = ? WHERE user_id = ?'
            );
            $stmt->execute([$companyName, $website, $description, $logoPath, $userId]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE employer_profiles SET company_name = ?, company_website = ?, company_description = ? WHERE user_id = ?'
            );
            $stmt->execute([$companyName, $website, $description, $userId]);
        }
    }
}
