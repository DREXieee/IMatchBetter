/**
 * Avatar upload preview. Markup contract:
 * <div class="avatar-upload">
 *   <div class="avatar-upload-preview" data-avatar-preview>
 *     <img data-avatar-preview-img alt="">
 *     <span class="avatar-upload-preview-icon">...</span>
 *   </div>
 *   <button type="button" data-avatar-trigger>Upload Photo</button>
 *   <input type="file" data-avatar-input class="avatar-upload-input">
 * </div>
 */
(function () {
    'use strict';

    function initAvatarUpload(input) {
        var wrap = input.closest('.avatar-upload');
        if (!wrap) return;

        var preview = wrap.querySelector('[data-avatar-preview]');
        var img = wrap.querySelector('[data-avatar-preview-img]');
        var trigger = wrap.querySelector('[data-avatar-trigger]');

        if (trigger) {
            trigger.addEventListener('click', function () { input.click(); });
        }

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file || !img || !preview) return;

            var reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
                preview.classList.add('has-image');
            };
            reader.readAsDataURL(file);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var inputs = document.querySelectorAll('[data-avatar-input]');
        Array.prototype.forEach.call(inputs, initAvatarUpload);
    });
})();
