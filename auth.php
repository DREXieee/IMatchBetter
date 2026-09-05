<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;

Guard::requireGuest();

$errors = [];
$loginError = null;
$email = '';
$fullName = '';
$accountType = '';
$companyName = '';
$companyWebsite = '';
$companyDescription = '';
$activeTab = ($_GET['tab'] ?? '') === 'signup' ? 'signup' : 'login';
$signupStartStep = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();
    $form = $_POST['form'] ?? '';

    if ($form === 'login') {
        $activeTab = 'login';
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $result = Auth::attempt($email, $password);
        if ($result['ok']) {
            $_SESSION['show_login_loading_screen'] = true;
            redirect(Auth::dashboardPathForRole((string) Auth::role()));
        }
        $loginError = $result['error'];
    } elseif ($form === 'signup') {
        $activeTab = 'signup';
        $accountType = $_POST['account_type'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');
        $companyWebsite = trim($_POST['company_website'] ?? '');
        $companyDescription = trim($_POST['company_description'] ?? '');

        if (!in_array($accountType, ['job_seeker', 'experienced', 'employer'], true)) {
            $errors['account_type'] = 'Please select an account type.';
        }
        if (empty($_POST['terms'])) {
            $errors['terms'] = 'You must accept the Terms of Use and Privacy Policy.';
        }

        if ($accountType === 'employer') {
            require __DIR__ . '/includes/handlers/register-employer-handler.php';
        } else {
            require __DIR__ . '/includes/handlers/register-applicant-handler.php';
        }

        // If we got here, creation didn't happen — show whichever step has the first error.
        $step1Fields = ['account_type', 'full_name', 'email', 'terms'];
        $signupStartStep = empty(array_intersect($step1Fields, array_keys($errors))) ? 2 : 1;
    }
}

$pageTitle = $activeTab === 'signup' ? 'Sign Up — IMatchBetter' : 'Log In — IMatchBetter';
$bodyClass = 'auth-page';
$extraStylesheets = ['css/wizard-auth.css'];
$extraScripts = ['js/auth-tabs.js', 'js/step-wizard.js'];
require __DIR__ . '/includes/header.php';
?>
<main class="auth-shell">
    <div class="auth-hero">
        <a href="<?= h(base_url('index.php')) ?>" class="brand" style="color:#fff;">
            <img src="<?= h(base_url('img/logo.png')) ?>" alt="" width="28" height="28" class="brand-logo">
            IMatch<span style="color:#fff;">Better</span>
        </a>
        <div>
            <div class="auth-hero-eyebrow">Careers, matched better</div>
            <h1>Find work that fits your skills, goals, and potential.</h1>
            <p>Build your professional identity, meet trusted employers, and discover opportunities made for you.</p>
        </div>
        <div class="auth-feature-pills">
            <span class="auth-feature-pill">Skills-first matches</span>
            <span class="auth-feature-pill">Graduate-friendly</span>
            <span class="auth-feature-pill">Professional network</span>
        </div>
    </div>

    <div class="auth-panel">
        <div class="auth-panel-inner" data-auth-tabs data-initial-tab="<?= h($activeTab) ?>">
            <div class="auth-tabs" role="tablist">
                <button type="button" class="auth-tab" data-auth-tab="login" role="tab">Sign In</button>
                <button type="button" class="auth-tab" data-auth-tab="signup" role="tab">Create Account</button>
            </div>

            <div class="auth-tab-panel" data-auth-panel="login">
                <h1>Welcome Back</h1>
                <p class="form-hint">Continue your career journey.</p>

                <?php if ($loginError): ?>
                    <div class="flash flash-error"><?= h($loginError) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= h(base_url('auth.php')) ?>" novalidate>
                    <?= Csrf::field() ?>
                    <input type="hidden" name="form" value="login">

                    <div class="form-group">
                        <label class="form-label" for="login_email">Email Address</label>
                        <input class="form-control" type="email" id="login_email" name="email" value="<?= h($activeTab === 'login' ? $email : '') ?>" required autofocus>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="login_password">Password</label>
                        <input class="form-control" type="password" id="login_password" name="password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                </form>

                <p style="margin-top:1rem;"><a href="<?= h(base_url('forgot-password.php')) ?>">Forgot password?</a></p>
            </div>

            <div class="auth-tab-panel" data-auth-panel="signup">
                <form method="post" action="<?= h(base_url('auth.php')) ?>" novalidate data-step-wizard data-start-step="<?= (int) $signupStartStep ?>">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="form" value="signup">

                    <div class="step-progress" data-step-progress>
                        <div class="step-progress-item" data-step-item="1"><span class="step-progress-circle">1</span></div>
                        <div class="step-progress-line"></div>
                        <div class="step-progress-item" data-step-item="2"><span class="step-progress-circle">2</span></div>
                    </div>

                    <fieldset data-step-panel data-step="1">
                        <h1>Let's get started</h1>
                        <p class="form-hint">Create your secure account</p>

                        <div class="form-group">
                            <label class="form-label" for="account_type">Account type</label>
                            <select class="form-control" id="account_type" name="account_type" required>
                                <option value="" <?= $accountType === '' ? 'selected' : '' ?> disabled>Select one</option>
                                <option value="job_seeker" <?= $accountType === 'job_seeker' ? 'selected' : '' ?>>Job seeker / Graduate</option>
                                <option value="experienced" <?= $accountType === 'experienced' ? 'selected' : '' ?>>Experienced Professional</option>
                                <option value="employer" <?= $accountType === 'employer' ? 'selected' : '' ?>>Employer / Recruiter</option>
                            </select>
                            <?php if (!empty($errors['account_type'])): ?><div class="form-error"><?= h($errors['account_type']) ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="full_name">Full name</label>
                            <input class="form-control" type="text" id="full_name" name="full_name" value="<?= h($fullName) ?>" required>
                            <?php if (!empty($errors['full_name'])): ?><div class="form-error"><?= h($errors['full_name']) ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="signup_email">Email Address</label>
                            <input class="form-control" type="email" id="signup_email" name="email" value="<?= h($activeTab === 'signup' ? $email : '') ?>" required>
                            <?php if (!empty($errors['email'])): ?><div class="form-error"><?= h($errors['email']) ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" name="terms" value="1" required>
                                I accept the Terms of Use and Privacy Policy.
                            </label>
                            <?php if (!empty($errors['terms'])): ?><div class="form-error"><?= h($errors['terms']) ?></div><?php endif; ?>
                        </div>

                        <div class="step-actions">
                            <span></span>
                            <button type="button" class="btn btn-primary" data-step-next>Continue</button>
                        </div>
                    </fieldset>

                    <fieldset data-step-panel data-step="2" hidden>
                        <h1>Secure your account</h1>
                        <p class="form-hint">Use at least 8 characters, including a letter and number</p>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input class="form-control" type="password" id="password" name="password" required minlength="8">
                            <?php if (!empty($errors['password'])): ?><div class="form-error"><?= h($errors['password']) ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password</label>
                            <input class="form-control" type="password" id="confirm_password" name="confirm_password" required minlength="8">
                            <?php if (!empty($errors['confirm_password'])): ?><div class="form-error"><?= h($errors['confirm_password']) ?></div><?php endif; ?>
                        </div>

                        <div data-employer-fields hidden>
                            <div class="form-group">
                                <label class="form-label" for="company_name">Company name</label>
                                <input class="form-control" type="text" id="company_name" name="company_name" value="<?= h($companyName) ?>">
                                <?php if (!empty($errors['company_name'])): ?><div class="form-error"><?= h($errors['company_name']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="company_website">Company website (optional)</label>
                                <input class="form-control" type="url" id="company_website" name="company_website" value="<?= h($companyWebsite) ?>" placeholder="https://">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="company_description">Short company description (optional)</label>
                                <textarea class="form-control" id="company_description" name="company_description" rows="3"><?= h($companyDescription) ?></textarea>
                            </div>
                        </div>

                        <div class="step-actions">
                            <button type="button" class="btn btn-outline" data-step-prev>Back</button>
                            <button type="submit" class="btn btn-primary">Create Account</button>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</main>
<script>
(function () {
    var typeSelect = document.getElementById('account_type');
    var employerFields = document.querySelector('[data-employer-fields]');
    var companyNameInput = document.getElementById('company_name');
    if (!typeSelect || !employerFields) return;

    function sync() {
        var isEmployer = typeSelect.value === 'employer';
        employerFields.hidden = !isEmployer;
        if (companyNameInput) companyNameInput.required = isEmployer;
    }
    typeSelect.addEventListener('change', sync);
    sync();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
