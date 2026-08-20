# IMatchBetter

A job board platform connecting applicants, employers, and admins. Applicants build a profile
and apply to jobs; employers post jobs and review applicants; admins moderate employers,
reviews, and complaints. Matching is skill-tag based (see `src/Services/SkillMatchService.php`)
rather than free-text keyword guessing.

## Requirements

- PHP 8.2+ with the `pdo_mysql`, `fileinfo`, and `mbstring` extensions
- MySQL/MariaDB (InnoDB, utf8mb4)
- [Composer](https://getcomposer.org/)
- Apache (e.g. via XAMPP) with `mod_rewrite` — or any web server that can serve this directory
  as the document root

## Setup

1. **Clone into your web server's document root.**
   With XAMPP on Windows, that's typically `C:\xampp\htdocs\imatchbetter`, served at
   `http://localhost/imatchbetter`.

2. **Install PHP dependencies:**
   ```
   composer install
   ```

3. **Configure environment variables:**
   ```
   cp .env.example .env
   ```
   Edit `.env` with your database credentials and (optionally) SMTP settings for outgoing mail
   (verification emails, notifications, password resets). `APP_URL`'s path segment (e.g.
   `/imatchbetter`) must match where the app is actually deployed — but its scheme and host are
   only a fallback: `base_url()` (`src/Helpers/functions.php`) prefers the current request's own
   host when one exists, so the same install serves correct links whether it's reached via
   `localhost`, `127.0.0.1`, or another device's LAN IP (e.g. testing on a phone on the same
   Wi-Fi), with no per-device config changes. The configured scheme/host is only used for
   CLI-run scripts that have no request to read from (e.g. `scripts/process-job-match-queue.php`).

4. **Create the database and load the schema:**
   ```
   mysql -u root -p < database/schema.sql
   ```
   This creates the `imatchbetter` database and all tables.

5. **(Optional) Load sample data** — demo admin/employer/applicant accounts, a couple of tagged
   job postings, and one seeded application, so there's something to look at immediately:
   ```
   mysql -u root -p < database/seed.sql
   ```
   Demo logins (all share the password shown):
   | Role      | Email                          | Password        |
   |-----------|--------------------------------|------------------|
   | Admin     | admin@imatchbetter.local        | Admin@12345      |
   | Employer  | employer@imatchbetter.local     | Employer@12345   |
   | Applicant | applicant@imatchbetter.local    | Applicant@12345  |

   `database/seed.sql` is dev-only — do not run it against a production database.

6. **Visit the app** at your configured `APP_URL` (e.g. `http://localhost/imatchbetter`).
   `uploads/resumes/`, `uploads/logos/`, and `uploads/certificates/` are created automatically
   on first upload (see `src/Services/FileUploadService.php`); make sure the web server user can
   write to `uploads/`.

## Background job: matching notifications

Publishing a job enqueues it in `job_match_queue` instead of scoring every applicant inline, so
posting a job stays fast regardless of applicant pool size. A separate worker drains that queue
and sends "job matches your skills" notifications. Schedule it to run periodically:

**Windows (Task Scheduler), every 5 minutes:**
```
schtasks /create /tn "IMatchBetter Job Match Queue" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\imatchbetter\scripts\process-job-match-queue.php" /sc minute /mo 5
```

**Linux/macOS (cron), same cadence:**
```
*/5 * * * * php /path/to/imatchbetter/scripts/process-job-match-queue.php >> /path/to/imatchbetter/logs/job-match-queue.log 2>&1
```

Without this scheduled, jobs still get posted and applied to normally — only the proactive
"a new job matches your skills" notification won't fire.

## Running tests

Tests run against a dedicated `imatchbetter_test` database — never the dev database.

1. Create the test database and load the schema into it. `schema.sql` hardcodes
   `CREATE DATABASE imatchbetter` and `USE imatchbetter` at the top, so loading it as-is always
   targets the `imatchbetter` database regardless of which database you connect to — swap the
   database name on the fly instead:
   ```
   sed 's/imatchbetter;/imatchbetter_test;/; s/DATABASE IF NOT EXISTS imatchbetter /DATABASE IF NOT EXISTS imatchbetter_test /' database/schema.sql | mysql -u root -p
   ```

2. Adjust `DB_USER`/`DB_PASS` in `.env.testing` if needed — it's used directly by
   `tests/bootstrap.php` and already points at `imatchbetter_test` by default.

3. Run the suite:
   ```
   composer test
   ```
   or directly:
   ```
   vendor/bin/phpunit
   ```

Every integration test runs inside a transaction that's rolled back afterward, so the test
database is never left with leftover data between runs.

## Project structure

- `src/Models` — data access, one class per table/concept
- `src/Services` — business logic that spans models (job search, skill matching, file uploads,
  mail, audit logging)
- `src/Auth` — session auth, CSRF, and role-based route guards
- `includes/` — shared layout partials and the app bootstrap
- `admin/`, `employer/`, `applicant/` — role-specific pages, guarded by `Auth\Guard`
- `database/schema.sql` — full schema; `database/seed.sql` — dev-only sample data
- `tests/Unit` — pure-function tests; `tests/Integration` — DB-backed tests via
  `tests/Integration/DatabaseTestCase.php`
