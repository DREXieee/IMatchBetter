-- Adds two free-text fields to job postings so employers can describe their hiring
-- (employment) process and interview/scheduling process for applicants to see up front.

USE imatchbetter;

ALTER TABLE jobs
    ADD COLUMN employment_process TEXT NULL AFTER requirements,
    ADD COLUMN scheduling_process TEXT NULL AFTER employment_process;
