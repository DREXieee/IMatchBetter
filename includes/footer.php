<footer class="site-footer">
    <div class="container">
        <span>&copy; <?= date('Y') ?> IMatchBetter. All rights reserved.</span>
        <span>Manila, Philippines</span>
    </div>
</footer>
<script src="<?= h(base_url('js/nav-toggle.js')) ?>"></script>
<?php if (!empty($extraScripts)): foreach ($extraScripts as $script): ?>
<script src="<?= h(base_url($script)) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
