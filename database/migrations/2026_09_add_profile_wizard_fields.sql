-- Adds the fields needed by the applicant profile wizard (photo, contact/address
-- details, experience level, expected salary) and two new features: connections
-- (Network) and 1:1 messaging. Run once against an existing `imatchbetter`
-- database that was created from schema.sql before this migration existed.

USE imatchbetter;

ALTER TABLE applicant_profiles
    ADD COLUMN photo_path          VARCHAR(255) NULL AFTER user_id,
    ADD COLUMN date_of_birth       DATE         NULL AFTER photo_path,
    ADD COLUMN gender              VARCHAR(40)  NULL AFTER date_of_birth,
    ADD COLUMN street_address      VARCHAR(255) NULL AFTER location,
    ADD COLUMN city                VARCHAR(120) NULL AFTER street_address,
    ADD COLUMN province            VARCHAR(120) NULL AFTER city,
    ADD COLUMN zip_code            VARCHAR(20)  NULL AFTER province,
    ADD COLUMN experience_level    VARCHAR(60)  NULL AFTER field_of_study,
    ADD COLUMN expected_salary_min INT UNSIGNED NULL AFTER experience_level,
    ADD COLUMN expected_salary_max INT UNSIGNED NULL AFTER expected_salary_min;

-- ---------------------------------------------------------------------
-- connections (professional network — request/accept between any two users)
-- ---------------------------------------------------------------------
CREATE TABLE connections (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_id  INT UNSIGNED NOT NULL,
    recipient_id  INT UNSIGNED NOT NULL,
    status        ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at  DATETIME NULL,
    UNIQUE KEY uq_connections_pair (requester_id, recipient_id),
    KEY idx_connections_recipient (recipient_id, status),
    CONSTRAINT fk_connections_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_connections_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- messages (plain 1:1 direct messages — "conversations" are derived by
-- grouping distinct sender/recipient pairs per user, no separate table)
-- ---------------------------------------------------------------------
CREATE TABLE messages (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id     INT UNSIGNED NOT NULL,
    recipient_id  INT UNSIGNED NOT NULL,
    body          TEXT NOT NULL,
    is_read       TINYINT(1) NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_messages_thread (sender_id, recipient_id, created_at),
    KEY idx_messages_recipient_unread (recipient_id, is_read),
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
