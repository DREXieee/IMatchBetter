/**
 * Sign In / Create Account tab toggle. Markup contract:
 * <div data-auth-tabs data-initial-tab="signup">
 *   <div class="auth-tabs">
 *     <button type="button" class="auth-tab" data-auth-tab="login">Sign In</button>
 *     <button type="button" class="auth-tab" data-auth-tab="signup">Create Account</button>
 *   </div>
 *   <div class="auth-tab-panel" data-auth-panel="login">...</div>
 *   <div class="auth-tab-panel" data-auth-panel="signup">...</div>
 * </div>
 */
(function () {
    'use strict';

    function initAuthTabs(root) {
        var tabs = Array.prototype.slice.call(root.querySelectorAll('[data-auth-tab]'));
        var panels = Array.prototype.slice.call(root.querySelectorAll('[data-auth-panel]'));
        if (!tabs.length || !panels.length) return;

        function activate(name) {
            tabs.forEach(function (tab) {
                tab.classList.toggle('is-active', tab.getAttribute('data-auth-tab') === name);
                tab.setAttribute('aria-selected', tab.getAttribute('data-auth-tab') === name ? 'true' : 'false');
            });
            panels.forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-auth-panel') !== name;
            });
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activate(tab.getAttribute('data-auth-tab'));
            });
        });

        var params = new URLSearchParams(window.location.search);
        var initial = params.get('tab') || root.getAttribute('data-initial-tab') || tabs[0].getAttribute('data-auth-tab');
        activate(initial);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var roots = document.querySelectorAll('[data-auth-tabs]');
        Array.prototype.forEach.call(roots, initAuthTabs);
    });
})();
