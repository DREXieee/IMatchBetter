-- Adds admin notification broadcasts: a way for an admin to push a message to every
-- user, or to everyone in one role, and see a history of what's been sent.

USE imatchbetter;

CREATE TABLE notification_broadcasts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id        INT UNSIGNED NOT NULL,
    target_role     ENUM('all','applicant','employer','admin') NOT NULL,
    message         VARCHAR(500) NOT NULL,
    recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notification_broadcasts_admin (admin_id),
    CONSTRAINT fk_notification_broadcasts_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
