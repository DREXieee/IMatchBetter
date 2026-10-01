/**
 * Page-load flourish: the animated butterfly logo (already made visible
 * pre-paint by the inline script right after #page-transition-overlay's
 * markup in includes/header.php) stays up for a brief minimum time on every
 * page load, then fades out.
 *
 * Deliberately NOT tied to link clicks or navigation timing. An earlier
 * version intercepted clicks, showed the overlay, and delayed navigation so
 * it'd be visible before the page unloaded — but that left the overlay
 * permanently stuck visible (and blocking clicks, since is-active enables
 * pointer-events) whenever the outgoing page got frozen into the browser's
 * back/forward cache mid-transition: bfcache restores resume the page
 * exactly as it was frozen, without re-running any load-time JS to clear it,
 * so pressing Back could bring the stuck overlay right back. Reacting only
 * to this page's own load lifecycle avoids that class of bug entirely —
 * nothing here ever waits on a click before navigating, so there's nothing
 * left in a "waiting to navigate" state for bfcache to freeze.
 */
(function () {
    'use strict';

    var overlay = document.getElementById('page-transition-overlay');
    if (!overlay) return;

    var MIN_DISPLAY_MS = 500;
    var HARD_TIMEOUT_MS = 4000; // safety net in case 'load' never fires

    var startedAt = Date.now();
    var hidden = false;

    function hide() {
        if (hidden) return;
        hidden = true;
        overlay.classList.remove('is-active');
    }

    function attemptHide() {
        var elapsed = Date.now() - startedAt;
        if (elapsed < MIN_DISPLAY_MS) {
            window.setTimeout(attemptHide, MIN_DISPLAY_MS - elapsed);
            return;
        }
        hide();
    }

    if (document.readyState === 'complete') {
        attemptHide();
    } else {
        window.addEventListener('load', attemptHide);
    }
    window.setTimeout(hide, HARD_TIMEOUT_MS);

    // Belt-and-suspenders for the one narrow case the load-only approach
    // above doesn't fully rule out: a page that gets frozen into bfcache
    // within its own first MIN_DISPLAY_MS (before attemptHide has run yet)
    // would still be cached mid-splash. Force it hidden on any bfcache
    // restore rather than relying on that timing.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) hide();
    });
})();
