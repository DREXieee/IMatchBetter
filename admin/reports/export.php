<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Guard;
use IMatchBetter\Config\Database;
use Dompdf\Dompdf;
use Dompdf\Options;

Guard::requireRole('admin');

$pdo = Database::connection();

$usersByRole = $pdo->query("SELECT role, COUNT(*) AS total FROM users GROUP BY role")->fetchAll();
$jobsByStatus = $pdo->query("SELECT status, COUNT(*) AS total FROM jobs GROUP BY status")->fetchAll();
$applicationsByStatus = $pdo->query("SELECT status, COUNT(*) AS total FROM applications GROUP BY status")->fetchAll();

$openComplaints = (int) $pdo->query("SELECT COUNT(*) FROM complaints WHERE status IN ('open', 'investigating')")->fetchColumn();
$pendingReviews = (int) $pdo->query(
    "SELECT (SELECT COUNT(*) FROM employer_reviews WHERE status = 'pending')
           + (SELECT COUNT(*) FROM applicant_reviews WHERE status = 'pending')"
)->fetchColumn();
$avgEmployerRating = (float) $pdo->query("SELECT COALESCE(AVG(rating), 0) FROM employer_reviews WHERE status = 'approved'")->fetchColumn();
$avgApplicantRating = (float) $pdo->query("SELECT COALESCE(AVG(rating), 0) FROM applicant_reviews WHERE status = 'approved'")->fetchColumn();
$interviewsThisWeek = (int) $pdo->query(
    "SELECT COUNT(*) FROM interviews WHERE scheduled_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)"
)->fetchColumn();

function reportRows(array $rows, string $labelKey, string $valueKey): string
{
    $html = '';
    foreach ($rows as $row) {
        $html .= '<tr><td>' . h(ucfirst((string) $row[$labelKey])) . '</td><td style="text-align:right;">' . (int) $row[$valueKey] . '</td></tr>';
    }
    return $html;
}

$generatedAt = date('F j, Y g:i A');
$avgRatingLabel = number_format($avgEmployerRating, 1) . ' / ' . number_format($avgApplicantRating, 1);
$usersByRoleRows = reportRows($usersByRole, 'role', 'total');
$jobsByStatusRows = reportRows($jobsByStatus, 'status', 'total');
$applicationsByStatusRows = reportRows($applicationsByStatus, 'status', 'total');

$html = <<<HTML
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; color: #1a1f36; font-size: 12px; }
    h1 { font-size: 20px; }
    .subtitle { color: #5b6270; margin-top: 4px; margin-bottom: 24px; }
    .stat-row { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
    .stat-row td { width: 25%; padding: 10px; border: 1px solid #e2e6ee; text-align: center; vertical-align: top; }
    .stat-value { font-size: 20px; font-weight: bold; color: #2f6fed; display: block; }
    .stat-label { font-size: 10px; color: #5b6270; }
    h3 { font-size: 13px; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px solid #e2e6ee; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data td { padding: 4px 6px; border-bottom: 1px solid #f0f1f5; }
    .sections { width: 100%; }
    .section { float: left; width: 31%; margin-right: 3%; }
    .section:last-child { margin-right: 0; }
    .sections:after { content: ""; display: block; clear: both; }
    .footer { margin-top: 24px; color: #9aa0ab; font-size: 10px; clear: both; }
</style>
</head>
<body>
    <h1>Reports &amp; Insights</h1>
    <p class="subtitle">Platform-wide activity at a glance &mdash; generated {$generatedAt}</p>

    <table class="stat-row">
        <tr>
            <td><span class="stat-value">{$openComplaints}</span><span class="stat-label">Open Complaints</span></td>
            <td><span class="stat-value">{$pendingReviews}</span><span class="stat-label">Reviews Awaiting Moderation</span></td>
            <td><span class="stat-value">{$interviewsThisWeek}</span><span class="stat-label">Interviews This Week</span></td>
            <td><span class="stat-value">{$avgRatingLabel}</span><span class="stat-label">Avg Rating (Employer / Applicant)</span></td>
        </tr>
    </table>

    <div class="sections">
        <div class="section">
            <h3>Users by Role</h3>
            <table class="data">{$usersByRoleRows}</table>
        </div>
        <div class="section">
            <h3>Jobs by Status</h3>
            <table class="data">{$jobsByStatusRows}</table>
        </div>
        <div class="section">
            <h3>Applications by Status</h3>
            <table class="data">{$applicationsByStatusRows}</table>
        </div>
    </div>

    <p class="footer">IMatchBetter &middot; Admin Reports Export</p>
</body>
</html>
HTML;

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'imatchbetter-report-' . date('Y-m-d') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
