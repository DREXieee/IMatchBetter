<?php

declare(strict_types=1);

namespace IMatchBetter\Tests\Integration;

use IMatchBetter\Models\EmailVerification;
use IMatchBetter\Models\User;

final class EmailVerificationTest extends DatabaseTestCase
{
    public function testNewUserIsUnverifiedUntilTokenIsConsumed(): void
    {
        $userId = $this->makeUser('applicant');
        $this->assertFalse(User::isEmailVerified($userId));

        $token = EmailVerification::create($userId);
        $row = EmailVerification::findValidByToken($token);

        $this->assertNotNull($row);
        $this->assertSame($userId, (int) $row['user_id']);

        User::markEmailVerified($userId);
        $this->assertTrue(User::isEmailVerified($userId));
    }

    public function testInvalidOrUnknownTokenIsRejected(): void
    {
        $this->assertNull(EmailVerification::findValidByToken('not-a-real-token'));
    }

    public function testInvalidateAllForUserBlocksReuse(): void
    {
        $userId = $this->makeUser('applicant');
        $token = EmailVerification::create($userId);

        EmailVerification::invalidateAllForUser($userId);

        $this->assertNull(EmailVerification::findValidByToken($token));
    }

    public function testHasRecentRequestPreventsImmediateResend(): void
    {
        $userId = $this->makeUser('applicant');
        $this->assertFalse(EmailVerification::hasRecentRequest($userId));

        EmailVerification::create($userId);

        $this->assertTrue(EmailVerification::hasRecentRequest($userId));
    }
}
