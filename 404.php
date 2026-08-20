<?php

require __DIR__ . '/includes/bootstrap.php';

http_response_code(404);

$pageTitle = 'Page Not Found — IMatchBetter';
require __DIR__ . '/includes/header.php';
?>
<main class="container" style="padding: 4rem 0; text-align:center;">
    <h1>404 — Page not found</h1>
    <p>The page you're looking for doesn't exist or may have moved.</p>
    <a href="<?= h(base_url('index.php')) ?>" class="btn btn-primary">Back to Home</a>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
