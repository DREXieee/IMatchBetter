/**
 * Adds a Show/Hide toggle to every password field on the page, automatically —
 * no per-page markup needed. Wraps each input[type=password] in a positioning
 * wrapper and appends a small text button that flips the input's type.
 */
(function () {
    'use strict';

    function wrapPasswordField(input) {
        if (input.closest('.password-field-wrap')) return;

        var wrap = document.createElement('span');
        wrap.className = 'password-field-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'password-field-toggle';
        toggle.textContent = 'Show';
        toggle.setAttribute('aria-label', 'Show password');
        wrap.appendChild(toggle);

        toggle.addEventListener('click', function () {
            var willShow = input.type === 'password';
            input.type = willShow ? 'text' : 'password';
            toggle.textContent = willShow ? 'Hide' : 'Show';
            toggle.setAttribute('aria-label', willShow ? 'Hide password' : 'Show password');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var inputs = document.querySelectorAll('input[type="password"]');
        Array.prototype.forEach.call(inputs, wrapPasswordField);
    });
})();
