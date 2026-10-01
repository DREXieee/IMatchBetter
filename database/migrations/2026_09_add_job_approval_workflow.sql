-- Adds a moderation workflow for job postings: a brand-new posting must be approved by an
-- admin before it becomes publicly visible (status='open'). Edits to a job that has already
-- been approved at least once do not require re-approval — see Job::update().

USE imatchbetter;

ALTER TABLE jobs
    ADD COLUMN approval_status ENUM('not_required','pending','approved','rejected') NOT NULL DEFAULT 'not_required' AFTER status,
    ADD COLUMN approval_requested_at DATETIME NULL AFTER approval_status,
    ADD COLUMN rejection_reason VARCHAR(500) NULL AFTER approval_requested_at,
    ADD COLUMN reviewed_by INT UNSIGNED NULL AFTER rejection_reason,
    ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by,
    ADD KEY idx_jobs_approval_status (approval_status),
    ADD CONSTRAINT fk_jobs_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL;

-- Backfill: any job that has ever been made public (open now, or closed after having been open)
-- already passed the old no-moderation bar — treat it as approved so editing it later doesn't
-- unexpectedly pull it offline pending a first-time review. Only genuinely new/never-published
-- drafts stay 'not_required'.
UPDATE jobs SET approval_status = 'approved' WHERE posted_at IS NOT NULL;
