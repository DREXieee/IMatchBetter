<?php

declare(strict_types=1);

namespace IMatchBetter\Tests\Unit;

use IMatchBetter\Services\SkillMatchService;
use PHPUnit\Framework\TestCase;

final class SkillMatchServiceTest extends TestCase
{
    public function testScoreIsZeroWhenJobHasNoTaggedSkills(): void
    {
        $this->assertSame(0.0, SkillMatchService::scoreTags([1, 2], [], []));
    }

    public function testScoreIsZeroWhenApplicantHasNoTaggedSkills(): void
    {
        $this->assertSame(0.0, SkillMatchService::scoreTags([], [1, 2], [3]));
    }

    public function testScoreIsZeroWhenNothingMatches(): void
    {
        $this->assertSame(0.0, SkillMatchService::scoreTags([99], [1, 2], [3]));
    }

    public function testScoreIsFullOverlapWhenAllRequiredAndPreferredMatch(): void
    {
        $this->assertSame(100.0, SkillMatchService::scoreTags([1, 2, 3], [1, 2], [3]));
    }

    public function testRequiredMatchesCountTwiceAsMuchAsPreferredMatches(): void
    {
        // 1 required skill + 1 preferred skill => weighted total = (1*2)+1 = 3.
        // Matching only the required skill: weighted matched = 2 => 2/3 = 66.7%.
        $this->assertSame(66.7, SkillMatchService::scoreTags([10], [10], [20]));
        // Matching only the preferred skill: weighted matched = 1 => 1/3 = 33.3%.
        $this->assertSame(33.3, SkillMatchService::scoreTags([20], [10], [20]));
    }

    public function testPartialRequiredOverlap(): void
    {
        // 2 required skills, applicant has 1 of them => weighted total = 4, matched = 2 => 50%.
        $this->assertSame(50.0, SkillMatchService::scoreTags([1], [1, 2], []));
    }
}
