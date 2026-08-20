<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

/**
 * A review of an employer, written by an applicant.
 */
class EmployerReview
{
    public static function create(int $employerId, int $applicantId, ?int $applicationId, int $rating, ?string $title, string $body): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO employer_reviews (employer_id, applicant_id, application_id, rating, title, body) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$employerId, $applicantId, $applicationId, $rating, $title, $body]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM employer_reviews WHERE id = ?');
        $stmt->execute([$id]);
        $review = $stmt->fetch();

        return $review ?: null;
    }

    public static function forEmployer(int $employerId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT er.*, u.full_name AS applicant_name
             FROM employer_reviews er
             JOIN users u ON u.id = er.applicant_id
             WHERE er.employer_id = ? AND er.status = 'approved'
             ORDER BY er.created_at DESC"
        );
        $stmt->execute([$employerId]);

        return $stmt->fetchAll();
    }

    public static function statsForEmployer(int $employerId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) AS review_count, COALESCE(AVG(rating), 0) AS avg_rating
             FROM employer_reviews WHERE employer_id = ? AND status = 'approved'"
        );
        $stmt->execute([$employerId]);

        return $stmt->fetch() ?: ['review_count' => 0, 'avg_rating' => 0];
    }

    public static function forApplicant(int $applicantId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT er.*, ep.company_name
             FROM employer_reviews er
             JOIN employer_profiles ep ON ep.user_id = er.employer_id
             WHERE er.applicant_id = ?
             ORDER BY er.created_at DESC'
        );
        $stmt->execute([$applicantId]);

        return $stmt->fetchAll();
    }

    public static function pending(): array
    {
        $stmt = Database::connection()->query(
            "SELECT er.*, applicant.full_name AS applicant_name, ep.company_name
             FROM employer_reviews er
             JOIN users applicant ON applicant.id = er.applicant_id
             JOIN employer_profiles ep ON ep.user_id = er.employer_id
             WHERE er.status = 'pending'
             ORDER BY er.created_at ASC
             LIMIT 200"
        );

        return $stmt->fetchAll();
    }

    public static function moderate(int $id, string $status, int $adminId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE employer_reviews SET status = ?, moderated_by = ?, moderated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$status, $adminId, $id]);
    }

    public static function hasReviewed(int $applicantId, int $employerId): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM employer_reviews WHERE applicant_id = ? AND employer_id = ?');
        $stmt->execute([$applicantId, $employerId]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Most recent approved reviews across all employers, for showcasing real testimonials
     * (e.g. on the landing page) rather than fabricated quotes.
     */
    public static function latestApproved(int $limit = 1): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT er.*, u.full_name AS applicant_name, ep.company_name
             FROM employer_reviews er
             JOIN users u ON u.id = er.applicant_id
             JOIN employer_profiles ep ON ep.user_id = er.employer_id
             WHERE er.status = 'approved'
             ORDER BY er.created_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function countRecentByAuthor(int $applicantId, int $hours = 24): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM employer_reviews WHERE applicant_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)'
        );
        $stmt->execute([$applicantId, $hours]);

        return (int) $stmt->fetchColumn();
    }
}
