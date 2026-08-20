(function () {
    var elements = document.querySelectorAll('[data-animate]');
    if (!elements.length) return;

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    elements.forEach(function (el) {
        el.classList.add('will-animate');
    });

    if (prefersReducedMotion || typeof IntersectionObserver === 'undefined') {
        elements.forEach(function (el) {
            el.classList.add('is-visible');
        });
        return;
    }

    var observer = new IntersectionObserver(function (entries, obs) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    elements.forEach(function (el) {
        observer.observe(el);
    });
})();
