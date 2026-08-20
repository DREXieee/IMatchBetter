<?php

declare(strict_types=1);

namespace IMatchBetter\Tests\Integration;

use IMatchBetter\Models\Notification;

final class NotificationOwnershipTest extends DatabaseTestCase
{
    public function testMarkReadOnlyAffectsTheOwningUser(): void
    {
        $owner = $this->makeUser('applicant');
        $stranger = $this->makeUser('applicant');

        $notificationId = Notification::create($owner, 'test', 'Hello');

        // A stranger trying to mark someone else's notification read should be a no-op.
        Notification::markRead($notificationId, $stranger);
        $this->assertSame(1, Notification::unreadCount($owner));

        Notification::markRead($notificationId, $owner);
        $this->assertSame(0, Notification::unreadCount($owner));
    }

    public function testMarkAllReadOnlyAffectsThatUsersNotifications(): void
    {
        $userA = $this->makeUser('applicant');
        $userB = $this->makeUser('applicant');

        Notification::create($userA, 'test', 'One');
        Notification::create($userA, 'test', 'Two');
        Notification::create($userB, 'test', 'Three');

        Notification::markAllRead($userA);

        $this->assertSame(0, Notification::unreadCount($userA));
        $this->assertSame(1, Notification::unreadCount($userB));
    }
}
