-- Adds employer verification documents (valid ID / company registration proof and a
-- company environment photo) captured at registration so admins can verify legitimacy
-- before approving an employer account.

USE imatchbetter;

ALTER TABLE employer_profiles
    ADD COLUMN valid_id_path VARCHAR(255) NULL AFTER logo_path,
    ADD COLUMN company_photo_path VARCHAR(255) NULL AFTER valid_id_path;
