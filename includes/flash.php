<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash-stack">
        <?php foreach ($_SESSION['flash'] as $flash): ?>
            <div class="flash flash-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
