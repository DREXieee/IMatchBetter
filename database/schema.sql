-- IMatchBetter schema
-- Run against a fresh `imatchbetter` database (utf8mb4, InnoDB).

CREATE DATABASE IF NOT EXISTS imatchbetter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE imatchbetter;

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email             VARCHAR(190) NOT NULL,
    password_hash     VARCHAR(255) NOT NULL,
    role              ENUM('applicant','employer','admin') NOT NULL,
    full_name         VARCHAR(150) NOT NULL,
    phone             VARCHAR(30) NULL,
    is_active         TINYINT(1) NOT NULL DEFAULT 1,
    failed_attempts   INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until      DATETIME NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- employer_profiles
-- ---------------------------------------------------------------------
CREATE TABLE employer_profiles (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id               INT UNSIGNED NOT NULL,
    company_name          VARCHAR(150) NOT NULL,
    company_website       VARCHAR(255) NULL,
    company_description   TEXT NULL,
    logo_path             VARCHAR(255) NULL,
    approval_status       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    rejection_reason      VARCHAR(500) NULL,
    reviewed_by           INT UNSIGNED NULL,
    reviewed_at           DATETIME NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_employer_profiles_user (user_id),
    KEY idx_employer_profiles_status (approval_status),
    CONSTRAINT fk_employer_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_employer_profiles_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- resumes (created before applicant_profiles so the FK below can point to it)
-- ---------------------------------------------------------------------
CREATE TABLE resumes (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    applicant_id       INT UNSIGNED NOT NULL,
    original_filename  VARCHAR(255) NOT NULL,
    stored_filename    VARCHAR(255) NOT NULL,
    file_path          VARCHAR(255) NOT NULL,
    mime_type          VARCHAR(100) NOT NULL,
    file_size          INT UNSIGNED NOT NULL,
    uploaded_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_resumes_applicant (applicant_id),
    CONSTRAINT fk_resumes_applicant FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- applicant_profiles
-- ---------------------------------------------------------------------
CREATE TABLE applicant_profiles (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL,
    headline            VARCHAR(150) NULL,
    bio                 TEXT NULL,
    location             VARCHAR(150) NULL,
    skills              TEXT NULL,
    current_resume_id   INT UNSIGNED NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_applicant_profiles_user (user_id),
    CONSTRAINT fk_applicant_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_applicant_profiles_resume FOREIGN KEY (current_resume_id) REFERENCES resumes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- jobs
-- ---------------------------------------------------------------------
CREATE TABLE jobs (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id       INT UNSIGNED NOT NULL,
    title             VARCHAR(150) NOT NULL,
    slug              VARCHAR(180) NOT NULL,
    description       TEXT NOT NULL,
    requirements      TEXT NULL,
    location          VARCHAR(150) NULL,
    employment_type   ENUM('full_time','part_time','contract','internship','remote') NOT NULL DEFAULT 'full_time',
    salary_min        INT UNSIGNED NULL,
    salary_max        INT UNSIGNED NULL,
    salary_currency   VARCHAR(10) NOT NULL DEFAULT 'PHP',
    category          VARCHAR(100) NULL,
    status            ENUM('open','closed','draft') NOT NULL DEFAULT 'draft',
    posted_at         DATETIME NULL,
    closed_at         DATETIME NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_jobs_slug (slug),
    KEY idx_jobs_employer (employer_id),
    KEY idx_jobs_status_location (status, location),
    FULLTEXT KEY ft_jobs_title_description (title, description),
    CONSTRAINT fk_jobs_employer FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- applications
-- ---------------------------------------------------------------------
CREATE TABLE applications (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id              INT UNSIGNED NOT NULL,
    applicant_id        INT UNSIGNED NOT NULL,
    resume_id           INT UNSIGNED NOT NULL,
    cover_letter        TEXT NULL,
    status              ENUM('submitted','reviewed','interview','rejected','hired') NOT NULL DEFAULT 'submitted',
    applied_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status_updated_at   DATETIME NULL,
    status_updated_by   INT UNSIGNED NULL,
    UNIQUE KEY uq_applications_job_applicant (job_id, applicant_id),
    KEY idx_applications_applicant (applicant_id),
    CONSTRAINT fk_applications_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_applications_applicant FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_applications_resume FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE RESTRICT,
    CONSTRAINT fk_applications_status_updated_by FOREIGN KEY (status_updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- admin_actions (audit log)
-- ---------------------------------------------------------------------
CREATE TABLE admin_actions (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id      INT UNSIGNED NOT NULL,
    action_type   VARCHAR(60) NOT NULL,
    target_type   VARCHAR(60) NOT NULL,
    target_id     INT UNSIGNED NOT NULL,
    notes         VARCHAR(500) NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admin_actions_admin (admin_id),
    KEY idx_admin_actions_target (target_type, target_id),
    CONSTRAINT fk_admin_actions_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- notifications
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    type          VARCHAR(60) NOT NULL,
    message       VARCHAR(500) NOT NULL,
    related_id    INT UNSIGNED NULL,
    related_type  VARCHAR(60) NULL,
    is_read       TINYINT(1) NOT NULL DEFAULT 0,
    email_sent    TINYINT(1) NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_user (user_id, is_read),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- password_resets
-- ---------------------------------------------------------------------
CREATE TABLE password_resets (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    token_hash    VARCHAR(255) NOT NULL,
    expires_at    DATETIME NOT NULL,
    used          TINYINT(1) NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_password_resets_user (user_id),
    KEY idx_password_resets_token_hash (token_hash),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- schema extensions: graduate profile fields, career growth, matching support
-- ---------------------------------------------------------------------
ALTER TABLE applicant_profiles
    ADD COLUMN school VARCHAR(150) NULL AFTER skills,
    ADD COLUMN degree VARCHAR(150) NULL AFTER school,
    ADD COLUMN field_of_study VARCHAR(150) NULL AFTER degree,
    ADD COLUMN graduation_year YEAR NULL AFTER field_of_study,
    ADD COLUMN profile_visibility TINYINT(1) NOT NULL DEFAULT 1 AFTER graduation_year;

ALTER TABLE jobs
    ADD COLUMN offers_training TINYINT(1) NOT NULL DEFAULT 0 AFTER category,
    ADD COLUMN career_growth_notes TEXT NULL AFTER offers_training;

-- ---------------------------------------------------------------------
-- certificates
-- ---------------------------------------------------------------------
CREATE TABLE certificates (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    applicant_id       INT UNSIGNED NOT NULL,
    original_filename  VARCHAR(255) NOT NULL,
    stored_filename    VARCHAR(255) NOT NULL,
    file_path          VARCHAR(255) NOT NULL,
    mime_type          VARCHAR(100) NOT NULL,
    file_size          INT UNSIGNED NOT NULL,
    uploaded_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_certificates_applicant (applicant_id),
    CONSTRAINT fk_certificates_applicant FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- job_preferences
-- ---------------------------------------------------------------------
CREATE TABLE job_preferences (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    applicant_id                INT UNSIGNED NOT NULL,
    preferred_employment_type   ENUM('full_time','part_time','contract','internship','remote','any') NOT NULL DEFAULT 'any',
    preferred_location          VARCHAR(150) NULL,
    salary_min                  INT UNSIGNED NULL,
    salary_max                  INT UNSIGNED NULL,
    created_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_job_preferences_applicant (applicant_id),
    CONSTRAINT fk_job_preferences_applicant FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- interviews
-- ---------------------------------------------------------------------
CREATE TABLE interviews (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id     INT UNSIGNED NOT NULL,
    scheduled_at       DATETIME NOT NULL,
    mode               ENUM('onsite','video','phone') NOT NULL,
    location_or_link   VARCHAR(255) NULL,
    notes              TEXT NULL,
    status             ENUM('proposed','confirmed','declined','rescheduled','completed','cancelled') NOT NULL DEFAULT 'proposed',
    created_by         INT UNSIGNED NOT NULL,
    responded_at       DATETIME NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_interviews_application (application_id),
    CONSTRAINT fk_interviews_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_interviews_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- employer_reviews (written by applicants, about an employer)
-- ---------------------------------------------------------------------
CREATE TABLE employer_reviews (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id     INT UNSIGNED NOT NULL,
    applicant_id    INT UNSIGNED NOT NULL,
    application_id  INT UNSIGNED NULL,
    rating          TINYINT UNSIGNED NOT NULL,
    title           VARCHAR(150) NULL,
    body            TEXT NOT NULL,
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    moderated_by    INT UNSIGNED NULL,
    moderated_at    DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_employer_reviews_pair (employer_id, applicant_id),
    KEY idx_employer_reviews_status (status),
    CONSTRAINT fk_employer_reviews_employer FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_employer_reviews_applicant FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_employer_reviews_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL,
    CONSTRAINT fk_employer_reviews_moderator FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- applicant_reviews (written by employers, about an applicant)
-- ---------------------------------------------------------------------
CREATE TABLE applicant_reviews (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    applicant_id    INT UNSIGNED NOT NULL,
    employer_id     INT UNSIGNED NOT NULL,
    application_id  INT UNSIGNED NULL,
    rating          TINYINT UNSIGNED NOT NULL,
    title           VARCHAR(150) NULL,
    body            TEXT NOT NULL,
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    moderated_by    INT UNSIGNED NULL,
    moderated_at    DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_applicant_reviews_pair (applicant_id, employer_id),
    KEY idx_applicant_reviews_status (status),
    CONSTRAINT fk_applicant_reviews_applicant FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_applicant_reviews_employer FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_applicant_reviews_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL,
    CONSTRAINT fk_applicant_reviews_moderator FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- complaints
-- ---------------------------------------------------------------------
CREATE TABLE complaints (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complainant_id     INT UNSIGNED NOT NULL,
    against_type       ENUM('user','job','application') NOT NULL,
    against_id         INT UNSIGNED NOT NULL,
    category           VARCHAR(60) NOT NULL,
    message            TEXT NOT NULL,
    status             ENUM('open','investigating','resolved','dismissed') NOT NULL DEFAULT 'open',
    resolution_notes   TEXT NULL,
    resolved_by        INT UNSIGNED NULL,
    resolved_at        DATETIME NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_complaints_status (status),
    KEY idx_complaints_against (against_type, against_id),
    CONSTRAINT fk_complaints_complainant FOREIGN KEY (complainant_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_complaints_resolver FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- schema extensions: email verification, async job-match queue
-- ---------------------------------------------------------------------
ALTER TABLE users
    ADD COLUMN email_verified_at DATETIME NULL AFTER is_active;

-- ---------------------------------------------------------------------
-- email_verifications
-- ---------------------------------------------------------------------
CREATE TABLE email_verifications (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    token_hash    VARCHAR(255) NOT NULL,
    expires_at    DATETIME NOT NULL,
    used          TINYINT(1) NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_email_verifications_user (user_id),
    KEY idx_email_verifications_token_hash (token_hash),
    CONSTRAINT fk_email_verifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- job_match_queue (fan-out for "smart job notification" processed off the request path)
-- ---------------------------------------------------------------------
CREATE TABLE job_match_queue (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id        INT UNSIGNED NOT NULL,
    status        ENUM('pending','done','failed') NOT NULL DEFAULT 'pending',
    error         VARCHAR(500) NULL,
    processed_at  DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_job_match_queue_status (status),
    CONSTRAINT fk_job_match_queue_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- saved_jobs (applicant bookmarks)
-- ---------------------------------------------------------------------
CREATE TABLE saved_jobs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    applicant_id  INT UNSIGNED NOT NULL,
    job_id        INT UNSIGNED NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_saved_jobs_applicant_job (applicant_id, job_id),
    KEY idx_saved_jobs_applicant (applicant_id),
    CONSTRAINT fk_saved_jobs_applicant FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_jobs_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- skills taxonomy: a canonical catalog plus applicant/job tag pivots, so matching
-- compares structured tag sets instead of guessing overlap from free-text prose.
-- The comma-separated "skills" UI inputs stay as-is; on save they're parsed and synced here.
-- ---------------------------------------------------------------------
CREATE TABLE skills (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    slug          VARCHAR(100) NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_skills_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE applicant_skills (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    applicant_id  INT UNSIGNED NOT NULL,
    skill_id      INT UNSIGNED NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_applicant_skills_pair (applicant_id, skill_id),
    KEY idx_applicant_skills_skill (skill_id),
    CONSTRAINT fk_applicant_skills_applicant FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_applicant_skills_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE job_skills (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id             INT UNSIGNED NOT NULL,
    skill_id           INT UNSIGNED NOT NULL,
    requirement_level  ENUM('required','preferred') NOT NULL DEFAULT 'required',
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_job_skills_pair (job_id, skill_id),
    KEY idx_job_skills_skill (skill_id),
    CONSTRAINT fk_job_skills_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_job_skills_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;
