<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Config\Database;
use IMatchBetter\Models\Certificate;
use IMatchBetter\Models\Resume;

if (isset($_GET['certificate_id'])) {
    Guard::requireLogin();

    $certificateId = (int) $_GET['certificate_id'];
    $certificate = Certificate::find($certificateId);

    if (!$certificate) {
        http_response_code(404);
        exit('File not found.');
    }

    $userId = (int) Auth::id();
    $role = Auth::role();

    $authorized = false;

    if ($role === 'admin') {
        $authorized = true;
    } elseif ($role === 'applicant' && (int) $certificate['applicant_id'] === $userId) {
        $authorized = true;
    } elseif ($role === 'employer') {
        // Employer may download this certificate only if its owner applied to one of their jobs.
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM applications a JOIN jobs j ON j.id = a.job_id
             WHERE a.applicant_id = ? AND j.employer_id = ?'
        );
        $stmt->execute([$certificate['applicant_id'], $userId]);
        $authorized = (bool) $stmt->fetchColumn();
    }

    if (!$authorized) {
        http_response_code(403);
        exit('You are not authorized to download this file.');
    }

    $fullPath = BASE_PATH . '/' . $certificate['file_path'];

    if (!is_file($fullPath)) {
        http_response_code(404);
        exit('File no longer exists.');
    }

    header('Content-Type: ' . $certificate['mime_type']);
    header('Content-Disposition: attachment; filename="' . basename($certificate['original_filename']) . '"');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
}

if (isset($_GET['resume_id'])) {
    Guard::requireLogin();

    $resumeId = (int) $_GET['resume_id'];
    $resume = Resume::find($resumeId);

    if (!$resume) {
        http_response_code(404);
        exit('File not found.');
    }

    $userId = (int) Auth::id();
    $role = Auth::role();

    $authorized = false;

    if ($role === 'admin') {
        $authorized = true;
    } elseif ($role === 'applicant' && (int) $resume['applicant_id'] === $userId) {
        $authorized = true;
    } elseif ($role === 'employer') {
        // Employer may download this resume only if it was submitted to one of their jobs.
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM applications a JOIN jobs j ON j.id = a.job_id
             WHERE a.resume_id = ? AND j.employer_id = ?'
        );
        $stmt->execute([$resumeId, $userId]);
        $authorized = (bool) $stmt->fetchColumn();
    }

    if (!$authorized) {
        http_response_code(403);
        exit('You are not authorized to download this file.');
    }

    $fullPath = BASE_PATH . '/' . $resume['file_path'];

    if (!is_file($fullPath)) {
        http_response_code(404);
        exit('File no longer exists.');
    }

    header('Content-Type: ' . $resume['mime_type']);
    header('Content-Disposition: attachment; filename="' . basename($resume['original_filename']) . '"');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
}

if (isset($_GET['logo'])) {
    // Company logos aren't sensitive — served publicly, but only ever a filename we already
    // recognize from employer_profiles, never an arbitrary path.
    $filename = basename((string) $_GET['logo']);

    $stmt = Database::connection()->prepare("SELECT logo_path FROM employer_profiles WHERE logo_path = ?");
    $stmt->execute(['uploads/logos/' . $filename]);
    $logoPath = $stmt->fetchColumn();

    if (!$logoPath) {
        http_response_code(404);
        exit('Logo not found.');
    }

    $fullPath = BASE_PATH . '/' . $logoPath;

    if (!is_file($fullPath)) {
        http_response_code(404);
        exit('Logo not found.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    header('Content-Type: ' . finfo_file($finfo, $fullPath));
    finfo_close($finfo);
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
}

http_response_code(400);
exit('No file specified.');
