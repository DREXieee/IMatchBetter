(function () {
    var toggle = document.querySelector('[data-logo-menu-toggle]');
    var menu = document.querySelector('[data-logo-menu]');

    if (!toggle || !menu) return;

    function closeMenu() {
        menu.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    }

    function openMenu() {
        menu.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
    }

    toggle.addEventListener('click', function (event) {
        event.stopPropagation();
        if (menu.classList.contains('is-open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    document.addEventListener('click', function (event) {
        if (!menu.contains(event.target) && event.target !== toggle) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeMenu();
    });
})();
