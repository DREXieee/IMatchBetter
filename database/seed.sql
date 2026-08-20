-- Dev-only sample data. Do not run against production.
USE imatchbetter;

-- Default admin login: admin@imatchbetter.local / Admin@12345
INSERT INTO users (email, password_hash, role, full_name, is_active, email_verified_at)
VALUES ('admin@imatchbetter.local', '$2y$10$T2Bshu4ULQfP2LELc5Zu8umdk7Kj26ugOzPRVozclj1vpMQXqJIEy', 'admin', 'Site Admin', 1, NOW());

-- Sample approved employer (password: Employer@12345, same hash reused for dev convenience)
INSERT INTO users (email, password_hash, role, full_name, is_active, email_verified_at)
VALUES ('employer@imatchbetter.local', '$2y$10$T2Bshu4ULQfP2LELc5Zu8umdk7Kj26ugOzPRVozclj1vpMQXqJIEy', 'employer', 'Demo Employer', 1, NOW());

INSERT INTO employer_profiles (user_id, company_name, company_website, company_description, approval_status, reviewed_by, reviewed_at)
VALUES (
    (SELECT id FROM users WHERE email = 'employer@imatchbetter.local'),
    'Acme Robotics',
    'https://acme.example',
    'We build friendly robots.',
    'approved',
    (SELECT id FROM users WHERE email = 'admin@imatchbetter.local'),
    NOW()
);

-- Sample applicant (password: Applicant@12345, same hash reused for dev convenience)
INSERT INTO users (email, password_hash, role, full_name, is_active, email_verified_at)
VALUES ('applicant@imatchbetter.local', '$2y$10$T2Bshu4ULQfP2LELc5Zu8umdk7Kj26ugOzPRVozclj1vpMQXqJIEy', 'applicant', 'Demo Applicant', 1, NOW());

INSERT INTO applicant_profiles (user_id, headline, bio, location, skills, school, degree, field_of_study, graduation_year)
VALUES (
    (SELECT id FROM users WHERE email = 'applicant@imatchbetter.local'),
    'Junior Web Developer',
    'Recent graduate looking for a front-end role.',
    'Manila, Philippines',
    'HTML, CSS, JavaScript, PHP',
    'University of the Philippines',
    'BS Computer Science',
    'Web Development',
    2024
);

-- Sample jobs
INSERT INTO jobs (employer_id, title, slug, description, requirements, location, employment_type, salary_min, salary_max, category, status, posted_at)
VALUES
(
    (SELECT id FROM users WHERE email = 'employer@imatchbetter.local'),
    'Front-End Developer',
    'front-end-developer',
    'Build and maintain our customer-facing web app.',
    '1+ years experience with HTML/CSS/JS. PHP a plus.',
    'Manila, Philippines',
    'full_time',
    25000, 35000,
    'Software Development',
    'open',
    NOW()
),
(
    (SELECT id FROM users WHERE email = 'employer@imatchbetter.local'),
    'Customer Support Specialist',
    'customer-support-specialist',
    'Handle inbound customer inquiries via chat and email.',
    'Good written English, comfortable with support tools.',
    'Remote',
    'remote',
    18000, 22000,
    'Customer Service',
    'open',
    NOW()
);

UPDATE jobs SET offers_training = 1, career_growth_notes = 'Structured mentorship track with a promotion review every 6 months.'
WHERE slug = 'front-end-developer';

-- Skill tags for the seed jobs, so the recommendation engine has real data to match against.
INSERT INTO skills (name, slug) VALUES
    ('HTML', 'html'),
    ('CSS', 'css'),
    ('JavaScript', 'javascript'),
    ('PHP', 'php'),
    ('Customer Service', 'customer-service'),
    ('English Communication', 'english-communication'),
    ('Help Desk Software', 'help-desk-software');

INSERT INTO job_skills (job_id, skill_id, requirement_level)
VALUES
    ((SELECT id FROM jobs WHERE slug = 'front-end-developer'), (SELECT id FROM skills WHERE slug = 'html'), 'required'),
    ((SELECT id FROM jobs WHERE slug = 'front-end-developer'), (SELECT id FROM skills WHERE slug = 'css'), 'required'),
    ((SELECT id FROM jobs WHERE slug = 'front-end-developer'), (SELECT id FROM skills WHERE slug = 'javascript'), 'required'),
    ((SELECT id FROM jobs WHERE slug = 'front-end-developer'), (SELECT id FROM skills WHERE slug = 'php'), 'preferred'),
    ((SELECT id FROM jobs WHERE slug = 'customer-support-specialist'), (SELECT id FROM skills WHERE slug = 'customer-service'), 'required'),
    ((SELECT id FROM jobs WHERE slug = 'customer-support-specialist'), (SELECT id FROM skills WHERE slug = 'english-communication'), 'required'),
    ((SELECT id FROM jobs WHERE slug = 'customer-support-specialist'), (SELECT id FROM skills WHERE slug = 'help-desk-software'), 'preferred');

-- Structured skill tags for the seed applicant, matching the free-text 'skills' field above.
INSERT INTO applicant_skills (applicant_id, skill_id)
VALUES
    ((SELECT id FROM users WHERE email = 'applicant@imatchbetter.local'), (SELECT id FROM skills WHERE slug = 'html')),
    ((SELECT id FROM users WHERE email = 'applicant@imatchbetter.local'), (SELECT id FROM skills WHERE slug = 'css')),
    ((SELECT id FROM users WHERE email = 'applicant@imatchbetter.local'), (SELECT id FROM skills WHERE slug = 'javascript')),
    ((SELECT id FROM users WHERE email = 'applicant@imatchbetter.local'), (SELECT id FROM skills WHERE slug = 'php'));

-- Job preferences for the demo applicant
INSERT INTO job_preferences (applicant_id, preferred_employment_type, preferred_location, salary_min, salary_max)
VALUES (
    (SELECT id FROM users WHERE email = 'applicant@imatchbetter.local'),
    'full_time',
    'Manila, Philippines',
    25000, 40000
);

-- Sample resume + application so the interview/review/complaint samples below have something to link to
INSERT INTO resumes (applicant_id, original_filename, stored_filename, file_path, mime_type, file_size)
VALUES (
    (SELECT id FROM users WHERE email = 'applicant@imatchbetter.local'),
    'demo-resume.pdf',
    'demo-resume-seed.pdf',
    'uploads/resumes/demo-resume-seed.pdf',
    'application/pdf',
    102400
);

UPDATE applicant_profiles SET current_resume_id = (SELECT id FROM resumes WHERE stored_filename = 'demo-resume-seed.pdf')
WHERE user_id = (SELECT id FROM users WHERE email = 'applicant@imatchbetter.local');

INSERT INTO certificates (applicant_id, original_filename, stored_filename, file_path, mime_type, file_size)
VALUES (
    (SELECT id FROM users WHERE email = 'applicant@imatchbetter.local'),
    'demo-certificate.pdf',
    'demo-certificate-seed.pdf',
    'uploads/certificates/demo-certificate-seed.pdf',
    'application/pdf',
    51200
);

INSERT INTO applications (job_id, applicant_id, resume_id, cover_letter, status, status_updated_at, status_updated_by)
VALUES (
    (SELECT id FROM jobs WHERE slug = 'front-end-developer'),
    (SELECT id FROM users WHERE email = 'applicant@imatchbetter.local'),
    (SELECT id FROM resumes WHERE stored_filename = 'demo-resume-seed.pdf'),
    'I would love to join Acme Robotics as a front-end developer.',
    'interview',
    NOW(),
    (SELECT id FROM users WHERE email = 'employer@imatchbetter.local')
);

INSERT INTO interviews (application_id, scheduled_at, mode, location_or_link, notes, status, created_by)
VALUES (
    (SELECT id FROM applications WHERE job_id = (SELECT id FROM jobs WHERE slug = 'front-end-developer') AND applicant_id = (SELECT id FROM users WHERE email = 'applicant@imatchbetter.local')),
    DATE_ADD(NOW(), INTERVAL 3 DAY),
    'video',
    'https://meet.example.com/acme-interview',
    'Bring a portfolio link.',
    'proposed',
    (SELECT id FROM users WHERE email = 'employer@imatchbetter.local')
);

-- Sample review (applicant -> employer), pending admin moderation
INSERT INTO employer_reviews (employer_id, applicant_id, application_id, rating, title, body, status)
VALUES (
    (SELECT id FROM users WHERE email = 'employer@imatchbetter.local'),
    (SELECT id FROM users WHERE email = 'applicant@imatchbetter.local'),
    (SELECT id FROM applications WHERE job_id = (SELECT id FROM jobs WHERE slug = 'front-end-developer') AND applicant_id = (SELECT id FROM users WHERE email = 'applicant@imatchbetter.local')),
    5,
    'Great communication',
    'Fast responses and a clear interview process.',
    'pending'
);

-- Sample review (employer -> applicant), pending admin moderation
INSERT INTO applicant_reviews (applicant_id, employer_id, application_id, rating, title, body, status)
VALUES (
    (SELECT id FROM users WHERE email = 'applicant@imatchbetter.local'),
    (SELECT id FROM users WHERE email = 'employer@imatchbetter.local'),
    (SELECT id FROM applications WHERE job_id = (SELECT id FROM jobs WHERE slug = 'front-end-developer') AND applicant_id = (SELECT id FROM users WHERE email = 'applicant@imatchbetter.local')),
    4,
    'Strong candidate',
    'Showed up prepared with good questions.',
    'pending'
);

-- Sample complaint, open
INSERT INTO complaints (complainant_id, against_type, against_id, category, message, status)
VALUES (
    (SELECT id FROM users WHERE email = 'applicant@imatchbetter.local'),
    'job',
    (SELECT id FROM jobs WHERE slug = 'customer-support-specialist'),
    'unresponsive_employer',
    'I applied two weeks ago and have not heard back at all.',
    'open'
);
