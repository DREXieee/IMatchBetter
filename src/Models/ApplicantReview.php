<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;

/**
 * A review of an applicant, written by an employer.
 */
class ApplicantReview
{
    public static function create(int $applicantId, int $employerId, ?int $applicationId, int $rating, ?string $title, string $body): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO applicant_reviews (applicant_id, employer_id, application_id, rating, title, body) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$applicantId, $employerId, $applicationId, $rating, $title, $body]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM applicant_reviews WHERE id = ?');
        $stmt->execute([$id]);
        $review = $stmt->fetch();

        return $review ?: null;
    }

    public static function forApplicant(int $applicantId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT ar.*, ep.company_name
             FROM applicant_reviews ar
             JOIN employer_profiles ep ON ep.user_id = ar.employer_id
             WHERE ar.applicant_id = ? AND ar.status = 'approved'
             ORDER BY ar.created_at DESC"
        );
        $stmt->execute([$applicantId]);

        return $stmt->fetchAll();
    }

    public static function forEmployer(int $employerId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ar.*, u.full_name AS applicant_name
             FROM applicant_reviews ar
             JOIN users u ON u.id = ar.applicant_id
             WHERE ar.employer_id = ?
             ORDER BY ar.created_at DESC'
        );
        $stmt->execute([$employerId]);

        return $stmt->fetchAll();
    }

    public static function pending(): array
    {
        $stmt = Database::connection()->query(
            "SELECT ar.*, applicant.full_name AS applicant_name, ep.company_name
             FROM applicant_reviews ar
             JOIN users applicant ON applicant.id = ar.applicant_id
             JOIN employer_profiles ep ON ep.user_id = ar.employer_id
             WHERE ar.status = 'pending'
             ORDER BY ar.created_at ASC
             LIMIT 200"
        );

        return $stmt->fetchAll();
    }

    public static function moderate(int $id, string $status, int $adminId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE applicant_reviews SET status = ?, moderated_by = ?, moderated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$status, $adminId, $id]);
    }

    public static function hasReviewed(int $employerId, int $applicantId): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM applicant_reviews WHERE employer_id = ? AND applicant_id = ?');
        $stmt->execute([$employerId, $applicantId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function countRecentByAuthor(int $employerId, int $hours = 24): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM applicant_reviews WHERE employer_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)'
        );
        $stmt->execute([$employerId, $hours]);

        return (int) $stmt->fetchColumn();
    }
}
