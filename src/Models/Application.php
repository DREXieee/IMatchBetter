<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

class Application
{
    public static function hasApplied(int $applicantId, int $jobId): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM applications WHERE applicant_id = ? AND job_id = ?');
        $stmt->execute([$applicantId, $jobId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function create(int $jobId, int $applicantId, int $resumeId, ?string $coverLetter): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO applications (job_id, applicant_id, resume_id, cover_letter) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$jobId, $applicantId, $resumeId, $coverLetter]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM applications WHERE id = ?');
        $stmt->execute([$id]);
        $application = $stmt->fetch();

        return $application ?: null;
    }

    public static function forApplicant(int $applicantId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, j.title AS job_title, j.slug AS job_slug, j.employer_id, ep.company_name
             FROM applications a
             JOIN jobs j ON j.id = a.job_id
             JOIN employer_profiles ep ON ep.user_id = j.employer_id
             WHERE a.applicant_id = ?
             ORDER BY a.applied_at DESC'
        );
        $stmt->execute([$applicantId]);

        return $stmt->fetchAll();
    }

    public static function forJob(int $jobId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, u.full_name AS applicant_name, u.email AS applicant_email
             FROM applications a
             JOIN users u ON u.id = a.applicant_id
             WHERE a.job_id = ?
             ORDER BY a.applied_at DESC'
        );
        $stmt->execute([$jobId]);

        return $stmt->fetchAll();
    }

    public static function findWithContext(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, j.title AS job_title, j.employer_id, u.full_name AS applicant_name, u.email AS applicant_email,
                    r.stored_filename, r.original_filename, r.file_path
             FROM applications a
             JOIN jobs j ON j.id = a.job_id
             JOIN users u ON u.id = a.applicant_id
             JOIN resumes r ON r.id = a.resume_id
             WHERE a.id = ?'
        );
        $stmt->execute([$id]);
        $application = $stmt->fetch();

        return $application ?: null;
    }

    public static function updateStatus(int $id, string $status, int $updatedBy): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE applications SET status = ?, status_updated_at = NOW(), status_updated_by = ? WHERE id = ?'
        );
        $stmt->execute([$status, $updatedBy, $id]);
    }

    public static function isForEmployer(int $applicationId, int $employerId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM applications a JOIN jobs j ON j.id = a.job_id WHERE a.id = ? AND j.employer_id = ?'
        );
        $stmt->execute([$applicationId, $employerId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function countAll(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM applications')->fetchColumn();
    }

    /**
     * Applicant-funnel counts per job for one employer, keyed by job_id then status.
     * Each job's entry also has a 'total' key. Used to avoid an N+1 query per job row.
     *
     * @return array<int, array<string, int>>
     */
    public static function countsByEmployer(int $employerId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.job_id, a.status, COUNT(*) AS total
             FROM applications a
             JOIN jobs j ON j.id = a.job_id
             WHERE j.employer_id = ?
             GROUP BY a.job_id, a.status'
        );
        $stmt->execute([$employerId]);

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $jobId = (int) $row['job_id'];
            $counts[$jobId] ??= ['total' => 0];
            $counts[$jobId][$row['status']] = (int) $row['total'];
            $counts[$jobId]['total'] += (int) $row['total'];
        }

        return $counts;
    }
}
