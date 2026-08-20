<?php

namespace IMatchBetter\Services;

use IMatchBetter\Config\Database;
use IMatchBetter\Models\JobPreference;
use IMatchBetter\Models\Skill;

/**
 * Matches applicants to jobs by comparing structured skill tags (see Models\Skill) instead of
 * guessing overlap from free-text prose. Required-skill matches count twice as much as
 * preferred-skill matches, so a job with several hard requirements scores meaningfully
 * differently from one that just lists a couple of nice-to-haves.
 */
class SkillMatchService
{
    /**
     * Weighted overlap (0-100) between an applicant's tagged skills and a job's required and
     * preferred skill tags. A job with no tagged skills always scores 0 — it hasn't been
     * tagged yet, so there's nothing structured to match against.
     *
     * @param int[] $applicantSkillIds
     * @param int[] $requiredSkillIds
     * @param int[] $preferredSkillIds
     */
    public static function scoreTags(array $applicantSkillIds, array $requiredSkillIds, array $preferredSkillIds): float
    {
        $weightedTotal = (count($requiredSkillIds) * 2) + count($preferredSkillIds);

        if ($weightedTotal === 0 || empty($applicantSkillIds)) {
            return 0.0;
        }

        $requiredMatched = count(array_intersect($applicantSkillIds, $requiredSkillIds));
        $preferredMatched = count(array_intersect($applicantSkillIds, $preferredSkillIds));
        $weightedMatched = ($requiredMatched * 2) + $preferredMatched;

        return round(($weightedMatched / $weightedTotal) * 100, 1);
    }

    public static function recommendedJobsForApplicant(int $applicantId, int $limit = 10): array
    {
        $applicantSkillIds = Skill::idsForApplicant($applicantId);

        if (empty($applicantSkillIds)) {
            return [];
        }

        $preference = JobPreference::findByApplicantId($applicantId);

        $stmt = Database::connection()->query(
            "SELECT j.*, ep.company_name
             FROM jobs j
             JOIN employer_profiles ep ON ep.user_id = j.employer_id
             WHERE j.status = 'open'
             ORDER BY j.posted_at DESC
             LIMIT 200"
        );
        $jobs = $stmt->fetchAll();

        if (empty($jobs)) {
            return [];
        }

        $jobSkills = Skill::idsForJobs(array_column($jobs, 'id'));

        $scored = [];
        foreach ($jobs as $job) {
            $tags = $jobSkills[(int) $job['id']] ?? ['required' => [], 'preferred' => []];
            $score = self::scoreTags($applicantSkillIds, $tags['required'], $tags['preferred']);

            // Preference bonuses only refine an existing skill match — an untagged job, or one
            // with zero overlapping skills, shouldn't show up here just because its location or
            // employment type happens to line up.
            if ($score > 0 && $preference) {
                if ($preference['preferred_employment_type'] !== 'any' && $preference['preferred_employment_type'] === $job['employment_type']) {
                    $score += 10;
                }
                if (!empty($preference['preferred_location']) && !empty($job['location'])
                    && stripos($job['location'], $preference['preferred_location']) !== false) {
                    $score += 10;
                }
            }

            if ($score > 0) {
                $job['match_score'] = min(100.0, $score);
                $scored[] = $job;
            }
        }

        usort($scored, static fn (array $a, array $b): int => $b['match_score'] <=> $a['match_score']);

        return array_slice($scored, 0, $limit);
    }

    public static function matchedApplicantsForJob(int $jobId): array
    {
        $tags = Skill::idsForJob($jobId);

        if (empty($tags['required']) && empty($tags['preferred'])) {
            return [];
        }

        $stmt = Database::connection()->prepare(
            'SELECT a.id AS application_id, a.applicant_id, a.status, a.applied_at
             FROM applications a
             WHERE a.job_id = ?'
        );
        $stmt->execute([$jobId]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return [];
        }

        $applicantSkills = Skill::idsForApplicants(array_column($rows, 'applicant_id'));

        foreach ($rows as &$row) {
            $applicantSkillIds = $applicantSkills[(int) $row['applicant_id']] ?? [];
            $row['match_score'] = self::scoreTags($applicantSkillIds, $tags['required'], $tags['preferred']);
        }
        unset($row);

        usort($rows, static fn (array $a, array $b): int => $b['match_score'] <=> $a['match_score']);

        return $rows;
    }

    /**
     * Applicants (whether they've applied or not) whose skills overlap this job by at least
     * $threshold percent, used to fan out "smart job" notifications when a job is published.
     */
    public static function applicantsAboveThreshold(int $jobId, float $threshold = 50.0): array
    {
        $tags = Skill::idsForJob($jobId);
        $allTagIds = array_merge($tags['required'], $tags['preferred']);

        if (empty($allTagIds)) {
            return [];
        }

        // Narrow to applicants who share at least one tag before scoring, instead of pulling
        // every visible applicant's skills into PHP.
        $candidateIds = Skill::applicantIdsWithAnySkill($allTagIds);

        if (empty($candidateIds)) {
            return [];
        }

        $applicantSkills = Skill::idsForApplicants($candidateIds);

        $matched = [];
        foreach ($candidateIds as $applicantId) {
            $score = self::scoreTags($applicantSkills[$applicantId] ?? [], $tags['required'], $tags['preferred']);

            if ($score >= $threshold) {
                $matched[] = ['user_id' => $applicantId, 'match_score' => $score];
            }
        }

        return $matched;
    }
}
