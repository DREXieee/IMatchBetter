(function () {
    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var elements = document.querySelectorAll('[data-animate]');

    if (elements.length) {
        elements.forEach(function (el) {
            el.classList.add('will-animate');
        });

        if (prefersReducedMotion || typeof IntersectionObserver === 'undefined') {
            elements.forEach(function (el) {
                el.classList.add('is-visible');
            });
        } else {
            var revealObserver = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

            elements.forEach(function (el) {
                revealObserver.observe(el);
            });
        }
    }

    // Count-up stat numbers: the server-rendered value is the correct final state (works
    // with no JS and under prefers-reduced-motion); when motion is allowed, count from 0
    // up to it once the element scrolls into view.
    var counters = document.querySelectorAll('[data-count-to]');
    if (!counters.length || prefersReducedMotion) return;

    var easeOutCubic = function (t) {
        return 1 - Math.pow(1 - t, 3);
    };

    var animateCount = function (el) {
        var target = parseInt(el.getAttribute('data-count-to'), 10);
        if (isNaN(target)) return;

        var duration = 1200;
        var start = null;

        el.textContent = '0';

        var step = function (timestamp) {
            if (start === null) start = timestamp;
            var progress = Math.min((timestamp - start) / duration, 1);
            el.textContent = Math.round(easeOutCubic(progress) * target);

            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                el.textContent = target;
            }
        };

        window.requestAnimationFrame(step);
    };

    if (typeof IntersectionObserver === 'undefined') {
        return;
    }

    var countObserver = new IntersectionObserver(function (entries, obs) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                animateCount(entry.target);
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });

    counters.forEach(function (el) {
        countObserver.observe(el);
    });
})();
