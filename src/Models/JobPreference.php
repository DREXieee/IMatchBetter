<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;

class JobPreference
{
    public static function findByApplicantId(int $applicantId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM job_preferences WHERE applicant_id = ?');
        $stmt->execute([$applicantId]);
        $preference = $stmt->fetch();

        return $preference ?: null;
    }

    public static function upsert(int $applicantId, string $preferredEmploymentType, ?string $preferredLocation, ?int $salaryMin, ?int $salaryMax): void
    {
        $pdo = Database::connection();

        if (self::findByApplicantId($applicantId) !== null) {
            $stmt = $pdo->prepare(
                'UPDATE job_preferences SET preferred_employment_type = ?, preferred_location = ?, salary_min = ?, salary_max = ? WHERE applicant_id = ?'
            );
            $stmt->execute([$preferredEmploymentType, $preferredLocation, $salaryMin, $salaryMax, $applicantId]);

            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO job_preferences (applicant_id, preferred_employment_type, preferred_location, salary_min, salary_max) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$applicantId, $preferredEmploymentType, $preferredLocation, $salaryMin, $salaryMax]);
    }
}
