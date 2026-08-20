<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;

class Interview
{
    public static function create(int $applicationId, string $scheduledAt, string $mode, ?string $locationOrLink, ?string $notes, int $createdBy): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO interviews (application_id, scheduled_at, mode, location_or_link, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$applicationId, $scheduledAt, $mode, $locationOrLink, $notes, $createdBy]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM interviews WHERE id = ?');
        $stmt->execute([$id]);
        $interview = $stmt->fetch();

        return $interview ?: null;
    }

    public static function findByApplication(int $applicationId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM interviews WHERE application_id = ?');
        $stmt->execute([$applicationId]);
        $interview = $stmt->fetch();

        return $interview ?: null;
    }

    public static function forApplicant(int $applicantId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT i.*, j.title AS job_title, ep.company_name, a.applicant_id
             FROM interviews i
             JOIN applications a ON a.id = i.application_id
             JOIN jobs j ON j.id = a.job_id
             JOIN employer_profiles ep ON ep.user_id = j.employer_id
             WHERE a.applicant_id = ?
             ORDER BY i.scheduled_at DESC'
        );
        $stmt->execute([$applicantId]);

        return $stmt->fetchAll();
    }

    public static function forEmployer(int $employerId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT i.*, j.title AS job_title, u.full_name AS applicant_name, a.applicant_id
             FROM interviews i
             JOIN applications a ON a.id = i.application_id
             JOIN jobs j ON j.id = a.job_id
             JOIN users u ON u.id = a.applicant_id
             WHERE j.employer_id = ?
             ORDER BY i.scheduled_at DESC'
        );
        $stmt->execute([$employerId]);

        return $stmt->fetchAll();
    }

    public static function isForApplicant(int $interviewId, int $applicantId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM interviews i JOIN applications a ON a.id = i.application_id WHERE i.id = ? AND a.applicant_id = ?'
        );
        $stmt->execute([$interviewId, $applicantId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function isForEmployer(int $interviewId, int $employerId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM interviews i JOIN applications a ON a.id = i.application_id JOIN jobs j ON j.id = a.job_id WHERE i.id = ? AND j.employer_id = ?'
        );
        $stmt->execute([$interviewId, $employerId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function reschedule(int $id, string $scheduledAt, string $mode, ?string $locationOrLink, ?string $notes): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE interviews SET scheduled_at = ?, mode = ?, location_or_link = ?, notes = ?, status = 'proposed', responded_at = NULL WHERE id = ?"
        );
        $stmt->execute([$scheduledAt, $mode, $locationOrLink, $notes, $id]);
    }

    public static function updateStatus(int $id, string $status, bool $respondedNow = false): void
    {
        if ($respondedNow) {
            $stmt = Database::connection()->prepare('UPDATE interviews SET status = ?, responded_at = NOW() WHERE id = ?');
        } else {
            $stmt = Database::connection()->prepare('UPDATE interviews SET status = ? WHERE id = ?');
        }
        $stmt->execute([$status, $id]);
    }
}
