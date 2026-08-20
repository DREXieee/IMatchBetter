<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

class SavedJob
{
    public static function isSaved(int $applicantId, int $jobId): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM saved_jobs WHERE applicant_id = ? AND job_id = ?');
        $stmt->execute([$applicantId, $jobId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function save(int $applicantId, int $jobId): void
    {
        $stmt = Database::connection()->prepare('INSERT IGNORE INTO saved_jobs (applicant_id, job_id) VALUES (?, ?)');
        $stmt->execute([$applicantId, $jobId]);
    }

    public static function unsave(int $applicantId, int $jobId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM saved_jobs WHERE applicant_id = ? AND job_id = ?');
        $stmt->execute([$applicantId, $jobId]);
    }

    public static function forApplicant(int $applicantId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT j.*, ep.company_name, sj.created_at AS saved_at
             FROM saved_jobs sj
             JOIN jobs j ON j.id = sj.job_id
             JOIN employer_profiles ep ON ep.user_id = j.employer_id
             WHERE sj.applicant_id = ?
             ORDER BY sj.created_at DESC'
        );
        $stmt->execute([$applicantId]);

        return $stmt->fetchAll();
    }

    /**
     * Set of job IDs the applicant has saved, for O(1) lookups when rendering job cards.
     *
     * @return array<int, true>
     */
    public static function savedJobIdSet(int $applicantId): array
    {
        $stmt = Database::connection()->prepare('SELECT job_id FROM saved_jobs WHERE applicant_id = ?');
        $stmt->execute([$applicantId]);

        return array_fill_keys(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)), true);
    }
}
