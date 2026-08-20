<footer class="site-footer">
    <div class="container">
        <span>&copy; <?= date('Y') ?> IMatchBetter. All rights reserved.</span>
        <nav class="footer-links">
            <a href="<?= h(base_url('jobs.php')) ?>">Find Jobs</a>
            <a href="<?= h(base_url('register-employer.php')) ?>">For Employers</a>
        </nav>
        <span class="footer-location">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
            Manila, Philippines
        </span>
    </div>
</footer>
<script src="<?= h(base_url('js/nav-toggle.js')) ?>"></script>
<?php if (!empty($extraScripts)): foreach ($extraScripts as $script): ?>
<script src="<?= h(base_url($script)) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
