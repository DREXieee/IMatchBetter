(function () {
    'use strict';

    var overlay = document.getElementById('loading-screen');
    if (!overlay) return;

    var percentEl = document.getElementById('loading-percent');
    var statusEl = document.getElementById('loading-status');
    var canvas = document.getElementById('loading-particles');

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    // prefers-reduced-motion disables the decorative animation (particles, pulsing, spinning,
    // the count-up) below — it does not shorten a wait time the page explicitly asked for.
    var minDisplayOverride = parseInt(overlay.getAttribute('data-min-display') || '', 10);
    var MIN_DISPLAY_MS = !isNaN(minDisplayOverride) ? minDisplayOverride : 1500;
    var HARD_TIMEOUT_MS = 8000; // safety net in case window 'load' never fires

    var startedAt = Date.now();
    var pageLoaded = false;
    var finished = false;

    if (document.body) {
        document.body.classList.add('is-loading');
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            document.body.classList.add('is-loading');
        });
    }

    var statusMessages = [
        'Scanning verified profiles…',
        'Connecting talent algorithms…',
        'Optimizing workspace match…'
    ];
    var statusIndex = 0;
    var statusTimer = null;

    if (statusEl) {
        statusTimer = window.setInterval(function () {
            statusIndex = (statusIndex + 1) % statusMessages.length;
            statusEl.textContent = statusMessages[statusIndex];
        }, 950);
    }

    // Tracks actual elapsed time against MIN_DISPLAY_MS (ease-out cubic) so the readout
    // reaches ~99% right as the minimum display duration elapses — no idle plateau before
    // finish() bumps it the rest of the way to 100%, regardless of how long that duration is.
    var progress = 0;
    var progressRaf = null;
    function stepProgress() {
        var elapsed = Date.now() - startedAt;
        var t = Math.min(elapsed / MIN_DISPLAY_MS, 1);
        var eased = 1 - Math.pow(1 - t, 3);
        var target = eased * 99;
        if (target > progress) progress = target;
        if (percentEl) percentEl.textContent = Math.floor(progress) + '%';
        if (!finished) {
            progressRaf = window.requestAnimationFrame(stepProgress);
        }
    }
    // The numeric count-up is a text update, not the kind of motion this preference is
    // meant to suppress, so it still runs for reduced-motion users — only the decorative
    // animation (particles, pulsing, spinning) below is skipped for them.
    progressRaf = window.requestAnimationFrame(stepProgress);

    // Constellation particle field — best-effort visual flourish, never allowed to
    // block or break the actual show/hide lifecycle below.
    var particleRaf = null;
    try {
        if (canvas && !prefersReducedMotion && typeof canvas.getContext === 'function') {
            initParticles(canvas);
        } else if (canvas) {
            canvas.style.display = 'none';
        }
    } catch (e) {
        if (canvas) canvas.style.display = 'none';
    }

    function initParticles(canvasEl) {
        var ctx = canvasEl.getContext('2d');
        if (!ctx) return;

        var dpr = Math.min(window.devicePixelRatio || 1, 2);
        var particles = [];
        var particleCount = 46;
        var linkDistance = 130;
        var width = 0;
        var height = 0;

        function resize() {
            width = window.innerWidth;
            height = window.innerHeight;
            canvasEl.width = width * dpr;
            canvasEl.height = height * dpr;
            canvasEl.style.width = width + 'px';
            canvasEl.style.height = height + 'px';
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        }

        function makeParticle() {
            return {
                x: Math.random() * width,
                y: Math.random() * height,
                vx: (Math.random() - 0.5) * 0.25,
                vy: (Math.random() - 0.5) * 0.25,
                r: 1 + Math.random() * 1.5
            };
        }

        resize();
        for (var i = 0; i < particleCount; i++) {
            particles.push(makeParticle());
        }

        var resizeTimer = null;
        window.addEventListener('resize', function () {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(resize, 200);
        });

        function draw() {
            ctx.clearRect(0, 0, width, height);

            for (var i = 0; i < particles.length; i++) {
                var p = particles[i];
                p.x += p.vx;
                p.y += p.vy;
                if (p.x < 0 || p.x > width) p.vx *= -1;
                if (p.y < 0 || p.y > height) p.vy *= -1;

                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(47, 111, 237, 0.45)';
                ctx.fill();
            }

            for (var a = 0; a < particles.length; a++) {
                for (var b = a + 1; b < particles.length; b++) {
                    var dx = particles[a].x - particles[b].x;
                    var dy = particles[a].y - particles[b].y;
                    var dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < linkDistance) {
                        ctx.beginPath();
                        ctx.moveTo(particles[a].x, particles[a].y);
                        ctx.lineTo(particles[b].x, particles[b].y);
                        ctx.strokeStyle = 'rgba(23, 184, 151, ' + (0.18 * (1 - dist / linkDistance)) + ')';
                        ctx.lineWidth = 1;
                        ctx.stroke();
                    }
                }
            }

            particleRaf = window.requestAnimationFrame(draw);
        }
        particleRaf = window.requestAnimationFrame(draw);
    }

    function attemptFinish() {
        var elapsed = Date.now() - startedAt;
        if (!pageLoaded || elapsed < MIN_DISPLAY_MS) {
            window.setTimeout(attemptFinish, Math.max(MIN_DISPLAY_MS - elapsed, 50));
            return;
        }
        finish();
    }

    function finish() {
        if (finished) return;
        finished = true;

        if (statusTimer) window.clearInterval(statusTimer);
        if (progressRaf) window.cancelAnimationFrame(progressRaf);
        if (percentEl) percentEl.textContent = '100%';

        window.setTimeout(function () {
            overlay.classList.add('is-hiding');
            if (document.body) document.body.classList.remove('is-loading');

            var removed = false;
            function remove() {
                if (removed) return;
                removed = true;
                if (particleRaf) window.cancelAnimationFrame(particleRaf);
                if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
            }
            overlay.addEventListener('transitionend', remove, { once: true });
            window.setTimeout(remove, 900); // fallback in case transitionend doesn't fire
        }, 200);
    }

    if (document.readyState === 'complete') {
        pageLoaded = true;
    } else {
        window.addEventListener('load', function () {
            pageLoaded = true;
        });
    }

    // Absolute upper bound: never let a hung resource keep this on screen forever.
    window.setTimeout(function () {
        pageLoaded = true;
    }, HARD_TIMEOUT_MS);

    attemptFinish();
})();
