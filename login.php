<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;

Guard::requireGuest();

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $result = Auth::attempt($email, $password);

    if ($result['ok']) {
        redirect(Auth::dashboardPathForRole((string) Auth::role()));
    }

    $error = $result['error'];
}

$pageTitle = 'Log In — IMatchBetter';
require __DIR__ . '/includes/header.php';
?>
<main class="container">
    <div class="form-card">
        <h1>Log in</h1>

        <?php if ($error): ?>
            <div class="flash flash-error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= h(base_url('login.php')) ?>" novalidate>
            <?= \IMatchBetter\Auth\Csrf::field() ?>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= h($email) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Log in</button>
        </form>

        <p style="margin-top:1rem; display:flex; justify-content:space-between;">
            <a href="<?= h(base_url('forgot-password.php')) ?>">Forgot password?</a>
            <a href="<?= h(base_url('register.php')) ?>">Create an account</a>
        </p>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
