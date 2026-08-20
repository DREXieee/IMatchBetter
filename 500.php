<?php

require __DIR__ . '/includes/bootstrap.php';

http_response_code(500);

$pageTitle = 'Something Went Wrong — IMatchBetter';
require __DIR__ . '/includes/header.php';
?>
<main class="container" style="padding: 4rem 0; text-align:center;">
    <h1>500 — Something went wrong</h1>
    <p>We hit an unexpected error on our end. Please try again in a moment.</p>
    <a href="<?= h(base_url('index.php')) ?>" class="btn btn-primary">Back to Home</a>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
