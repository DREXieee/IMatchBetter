<?php

declare(strict_types=1);

namespace IMatchBetter\Tests\Integration;

use IMatchBetter\Models\Complaint;

final class ComplaintTest extends DatabaseTestCase
{
    public function testCreateStartsOpenAndUpdateStatusRecordsResolution(): void
    {
        $complainant = $this->makeUser('applicant');
        $admin = $this->makeUser('admin');
        $employer = $this->makeUser('employer');
        $job = $this->makeJob($employer);

        $complaintId = Complaint::create($complainant, 'job', $job, 'fake_job', 'This listing looks fake.');

        $complaint = Complaint::find($complaintId);
        $this->assertSame('open', $complaint['status']);
        $this->assertNull($complaint['resolved_at']);

        Complaint::updateStatus($complaintId, 'resolved', 'Confirmed with employer, listing is legitimate.', $admin);

        $updated = Complaint::find($complaintId);
        $this->assertSame('resolved', $updated['status']);
        $this->assertSame($admin, (int) $updated['resolved_by']);
        $this->assertNotNull($updated['resolved_at']);
        $this->assertStringContainsString('legitimate', $updated['resolution_notes']);
    }

    public function testForUserOnlyReturnsThatUsersComplaints(): void
    {
        $userA = $this->makeUser('applicant');
        $userB = $this->makeUser('applicant');
        $employer = $this->makeUser('employer');
        $job = $this->makeJob($employer);

        Complaint::create($userA, 'job', $job, 'other', 'From user A.');
        Complaint::create($userB, 'job', $job, 'other', 'From user B.');

        $results = Complaint::forUser($userA);

        $this->assertCount(1, $results);
        $this->assertSame('From user A.', $results[0]['message']);
    }

    public function testAllFiltersByStatus(): void
    {
        $complainant = $this->makeUser('applicant');
        $admin = $this->makeUser('admin');
        $employer = $this->makeUser('employer');
        $job = $this->makeJob($employer);

        $openId = Complaint::create($complainant, 'job', $job, 'other', 'Still open.');
        $resolvedId = Complaint::create($complainant, 'job', $job, 'other', 'Already handled.');
        Complaint::updateStatus($resolvedId, 'resolved', null, $admin);

        $openOnly = Complaint::all('open');
        $ids = array_column($openOnly['complaints'], 'id');

        $this->assertContains($openId, $ids);
        $this->assertNotContains($resolvedId, $ids);
    }
}
