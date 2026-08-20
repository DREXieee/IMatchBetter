<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDOException;
use PDO;

/**
 * Canonical skills catalog. Applicant/job "skills" inputs stay plain comma-separated text
 * fields in the UI; on save, the text is parsed and synced into this structured catalog so
 * SkillMatchService can compare tag sets instead of guessing overlap from free-text prose.
 */
class Skill
{
    /**
     * Splits a comma-separated string into a trimmed, deduplicated (case-insensitive) list,
     * preserving the casing of each name's first occurrence.
     *
     * @return string[]
     */
    public static function parseList(string $commaSeparated): array
    {
        $seen = [];
        $result = [];

        foreach (explode(',', $commaSeparated) as $raw) {
            $name = trim(preg_replace('/\s+/', ' ', $raw) ?? '');

            if ($name === '' || isset($seen[mb_strtolower($name)])) {
                continue;
            }

            $seen[mb_strtolower($name)] = true;
            $result[] = $name;
        }

        return $result;
    }

    private static function slugify(string $name): string
    {
        $slug = mb_strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9\+\#]+/', '-', $slug) ?? '';

        return trim($slug, '-');
    }

    /**
     * IDs already in the catalog for the given names — no find-or-create, since a search
     * shouldn't mint new skills just because someone mistyped one. May return fewer IDs than
     * $names if some don't exist yet.
     *
     * @param string[] $names
     * @return int[]
     */
    public static function idsForExistingNames(array $names): array
    {
        $slugs = array_values(array_unique(array_filter(array_map([self::class, 'slugify'], $names))));

        if (empty($slugs)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $stmt = Database::connection()->prepare("SELECT id FROM skills WHERE slug IN ({$placeholders})");
        $stmt->execute($slugs);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Finds or creates skills by display name, returning their IDs (deduplicated by slug).
     *
     * @param string[] $names
     * @return int[]
     */
    public static function findOrCreateIdsByNames(array $names): array
    {
        if (empty($names)) {
            return [];
        }

        $pdo = Database::connection();
        $find = $pdo->prepare('SELECT id FROM skills WHERE slug = ?');
        $insert = $pdo->prepare('INSERT INTO skills (name, slug) VALUES (?, ?)');

        $ids = [];
        $seenSlugs = [];

        foreach ($names as $name) {
            $slug = self::slugify($name);

            if ($slug === '' || isset($seenSlugs[$slug])) {
                continue;
            }
            $seenSlugs[$slug] = true;

            $find->execute([$slug]);
            $existingId = $find->fetchColumn();

            if ($existingId !== false) {
                $ids[] = (int) $existingId;
                continue;
            }

            try {
                $insert->execute([$name, $slug]);
                $ids[] = (int) $pdo->lastInsertId();
            } catch (PDOException $e) {
                // Lost a race with another request creating the same slug; look it up instead.
                $find->execute([$slug]);
                $existingId = $find->fetchColumn();
                if ($existingId !== false) {
                    $ids[] = (int) $existingId;
                }
            }
        }

        return $ids;
    }

    /**
     * @param string[] $names
     */
    public static function syncApplicantSkills(int $applicantId, array $names): void
    {
        $ids = self::findOrCreateIdsByNames($names);
        $pdo = Database::connection();

        $pdo->prepare('DELETE FROM applicant_skills WHERE applicant_id = ?')->execute([$applicantId]);

        if (!empty($ids)) {
            $insert = $pdo->prepare('INSERT INTO applicant_skills (applicant_id, skill_id) VALUES (?, ?)');
            foreach ($ids as $skillId) {
                $insert->execute([$applicantId, $skillId]);
            }
        }
    }

    /**
     * A skill listed in both lists is treated as required.
     *
     * @param string[] $requiredNames
     * @param string[] $preferredNames
     */
    public static function syncJobSkills(int $jobId, array $requiredNames, array $preferredNames): void
    {
        $requiredLower = array_map('mb_strtolower', $requiredNames);
        $preferredNames = array_values(array_filter(
            $preferredNames,
            static fn (string $name): bool => !in_array(mb_strtolower($name), $requiredLower, true)
        ));

        $requiredIds = self::findOrCreateIdsByNames($requiredNames);
        $preferredIds = self::findOrCreateIdsByNames($preferredNames);

        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM job_skills WHERE job_id = ?')->execute([$jobId]);

        $insert = $pdo->prepare('INSERT INTO job_skills (job_id, skill_id, requirement_level) VALUES (?, ?, ?)');
        foreach ($requiredIds as $skillId) {
            $insert->execute([$jobId, $skillId, 'required']);
        }
        foreach ($preferredIds as $skillId) {
            $insert->execute([$jobId, $skillId, 'preferred']);
        }
    }

    /**
     * @return int[]
     */
    public static function idsForApplicant(int $applicantId): array
    {
        $stmt = Database::connection()->prepare('SELECT skill_id FROM applicant_skills WHERE applicant_id = ?');
        $stmt->execute([$applicantId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Batch version of idsForApplicant(), to avoid an N+1 query when scoring many applicants.
     *
     * @param int[] $applicantIds
     * @return array<int, int[]> keyed by applicant_id
     */
    public static function idsForApplicants(array $applicantIds): array
    {
        $applicantIds = array_values(array_unique(array_map('intval', $applicantIds)));

        if (empty($applicantIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($applicantIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT applicant_id, skill_id FROM applicant_skills WHERE applicant_id IN ({$placeholders})"
        );
        $stmt->execute($applicantIds);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int) $row['applicant_id']][] = (int) $row['skill_id'];
        }

        return $result;
    }

    /**
     * @return array{required: int[], preferred: int[]}
     */
    public static function idsForJob(int $jobId): array
    {
        $stmt = Database::connection()->prepare('SELECT skill_id, requirement_level FROM job_skills WHERE job_id = ?');
        $stmt->execute([$jobId]);

        $result = ['required' => [], 'preferred' => []];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['requirement_level']][] = (int) $row['skill_id'];
        }

        return $result;
    }

    /**
     * Batch version of idsForJob(), to avoid an N+1 query when scoring many jobs.
     *
     * @param int[] $jobIds
     * @return array<int, array{required: int[], preferred: int[]}> keyed by job_id
     */
    public static function idsForJobs(array $jobIds): array
    {
        $jobIds = array_values(array_unique(array_map('intval', $jobIds)));

        if (empty($jobIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT job_id, skill_id, requirement_level FROM job_skills WHERE job_id IN ({$placeholders})"
        );
        $stmt->execute($jobIds);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $jobId = (int) $row['job_id'];
            $result[$jobId] ??= ['required' => [], 'preferred' => []];
            $result[$jobId][$row['requirement_level']][] = (int) $row['skill_id'];
        }

        return $result;
    }

    /**
     * Display names for a job's tagged skills, grouped by requirement level — used to
     * pre-fill the job form and to show skill tags on the job detail page.
     *
     * @return array{required: string[], preferred: string[]}
     */
    public static function namesForJob(int $jobId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.name, js.requirement_level
             FROM job_skills js
             JOIN skills s ON s.id = js.skill_id
             WHERE js.job_id = ?
             ORDER BY s.name ASC'
        );
        $stmt->execute([$jobId]);

        $result = ['required' => [], 'preferred' => []];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['requirement_level']][] = $row['name'];
        }

        return $result;
    }

    /**
     * IDs of visible, active applicants who have at least one of the given skills tagged —
     * used to narrow the candidate pool before scoring, instead of pulling every visible
     * applicant's skills into PHP.
     *
     * @param int[] $skillIds
     * @return int[]
     */
    public static function applicantIdsWithAnySkill(array $skillIds): array
    {
        $skillIds = array_values(array_unique(array_map('intval', $skillIds)));

        if (empty($skillIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT DISTINCT sa.applicant_id
             FROM applicant_skills sa
             JOIN applicant_profiles ap ON ap.user_id = sa.applicant_id
             JOIN users u ON u.id = sa.applicant_id
             WHERE sa.skill_id IN ({$placeholders}) AND ap.profile_visibility = 1 AND u.is_active = 1"
        );
        $stmt->execute($skillIds);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
