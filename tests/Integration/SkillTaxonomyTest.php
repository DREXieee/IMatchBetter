<?php

declare(strict_types=1);

namespace IMatchBetter\Tests\Integration;

use IMatchBetter\Models\ApplicantProfile;
use IMatchBetter\Models\JobPreference;
use IMatchBetter\Models\Skill;
use IMatchBetter\Services\SkillMatchService;

final class SkillTaxonomyTest extends DatabaseTestCase
{
    public function testFindOrCreateIdsByNamesDedupesCaseInsensitivelyAndReusesExisting(): void
    {
        $firstPass = Skill::findOrCreateIdsByNames(['PHP', 'MySQL', 'php']);
        $this->assertCount(2, $firstPass);

        $secondPass = Skill::findOrCreateIdsByNames(['php', 'mysql']);
        $this->assertSame($firstPass, $secondPass);
    }

    public function testSyncApplicantSkillsReplacesThePreviousSet(): void
    {
        $applicant = $this->makeUser('applicant');

        Skill::syncApplicantSkills($applicant, ['PHP', 'MySQL']);
        $this->assertCount(2, Skill::idsForApplicant($applicant));

        Skill::syncApplicantSkills($applicant, ['React']);
        $ids = Skill::idsForApplicant($applicant);
        $this->assertCount(1, $ids);

        [$reactId] = Skill::findOrCreateIdsByNames(['React']);
        $this->assertSame([$reactId], $ids);
    }

    public function testSyncJobSkillsTreatsASkillListedInBothAsRequired(): void
    {
        $employer = $this->makeUser('employer');
        $job = $this->makeJob($employer);

        Skill::syncJobSkills($job, ['PHP'], ['PHP', 'MySQL']);

        $tags = Skill::idsForJob($job);
        [$phpId] = Skill::findOrCreateIdsByNames(['PHP']);
        [$mysqlId] = Skill::findOrCreateIdsByNames(['MySQL']);

        $this->assertSame([$phpId], $tags['required']);
        $this->assertSame([$mysqlId], $tags['preferred']);
    }

    public function testRecommendedJobsForApplicantWeighsRequiredSkillsHigher(): void
    {
        $employer = $this->makeUser('employer');
        $this->makeEmployerProfile($employer);
        $applicant = $this->makeUser('applicant');
        $this->makeApplicantProfile($applicant);

        $strongMatchJob = $this->makeJob($employer, 'Backend Role');
        $weakMatchJob = $this->makeJob($employer, 'Frontend Role');

        Skill::syncJobSkills($strongMatchJob, ['PHP', 'MySQL'], []);
        Skill::syncJobSkills($weakMatchJob, ['React', 'CSS'], ['PHP']);

        Skill::syncApplicantSkills($applicant, ['PHP', 'MySQL']);

        $recommended = SkillMatchService::recommendedJobsForApplicant($applicant, 10);
        $recommendedIds = array_column($recommended, 'id');

        $this->assertSame($strongMatchJob, $recommendedIds[0]);
        $this->assertGreaterThan(
            $recommended[array_search($weakMatchJob, $recommendedIds, true)]['match_score'],
            $recommended[0]['match_score']
        );
    }

    public function testUntaggedJobIsNeverRecommendedEvenWithMatchingPreferences(): void
    {
        $employer = $this->makeUser('employer');
        $this->makeEmployerProfile($employer);
        $applicant = $this->makeUser('applicant');
        $this->makeApplicantProfile($applicant);

        // A job with zero tagged skills, but whose location/employment type line up exactly
        // with the applicant's stated preferences.
        $untaggedJob = $this->makeJob($employer, 'Untagged Role');
        $this->pdo->prepare("UPDATE jobs SET employment_type = 'remote', location = 'Manila' WHERE id = ?")
            ->execute([$untaggedJob]);

        JobPreference::upsert($applicant, 'remote', 'Manila', null, null);
        Skill::syncApplicantSkills($applicant, ['PHP']);

        $recommended = SkillMatchService::recommendedJobsForApplicant($applicant, 10);

        $this->assertNotContains($untaggedJob, array_column($recommended, 'id'));
    }

    public function testSearchGraduatesRequiresAllListedSkillsToMatch(): void
    {
        $fullMatch = $this->makeUser('applicant');
        $this->makeApplicantProfile($fullMatch, 'PHP, MySQL');
        Skill::syncApplicantSkills($fullMatch, ['PHP', 'MySQL']);

        $partialMatch = $this->makeUser('applicant');
        $this->makeApplicantProfile($partialMatch, 'PHP');
        Skill::syncApplicantSkills($partialMatch, ['PHP']);

        $results = ApplicantProfile::searchGraduates(['skills' => 'PHP, MySQL']);
        $matchedIds = array_column($results['graduates'], 'user_id');

        $this->assertContains($fullMatch, $matchedIds);
        $this->assertNotContains($partialMatch, $matchedIds);

        // Searching for just one of the two skills should catch both.
        $singleSkillResults = ApplicantProfile::searchGraduates(['skills' => 'PHP']);
        $singleSkillIds = array_column($singleSkillResults['graduates'], 'user_id');

        $this->assertContains($fullMatch, $singleSkillIds);
        $this->assertContains($partialMatch, $singleSkillIds);
    }

    public function testSearchGraduatesReturnsNoResultsForASkillThatWasNeverTagged(): void
    {
        $applicant = $this->makeUser('applicant');
        $this->makeApplicantProfile($applicant, 'PHP');
        Skill::syncApplicantSkills($applicant, ['PHP']);

        $results = ApplicantProfile::searchGraduates(['skills' => 'SomeSkillNobodyHasEverTagged']);

        $this->assertSame(0, $results['total']);
        $this->assertSame([], $results['graduates']);
    }

    public function testApplicantsAboveThresholdOnlyReturnsVisibleActiveMatches(): void
    {
        $employer = $this->makeUser('employer');
        $job = $this->makeJob($employer);
        Skill::syncJobSkills($job, ['PHP'], []);

        $visibleApplicant = $this->makeUser('applicant');
        $this->makeApplicantProfile($visibleApplicant);
        Skill::syncApplicantSkills($visibleApplicant, ['PHP']);

        $hiddenApplicant = $this->makeUser('applicant');
        $this->pdo->prepare('INSERT INTO applicant_profiles (user_id, profile_visibility) VALUES (?, 0)')
            ->execute([$hiddenApplicant]);
        Skill::syncApplicantSkills($hiddenApplicant, ['PHP']);

        $matched = SkillMatchService::applicantsAboveThreshold($job, 50.0);
        $matchedIds = array_column($matched, 'user_id');

        $this->assertContains($visibleApplicant, $matchedIds);
        $this->assertNotContains($hiddenApplicant, $matchedIds);
    }
}
