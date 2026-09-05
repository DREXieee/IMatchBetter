/**
 * Generic, step-count-agnostic wizard controller. Drives both the 2-step
 * signup and the 4-step profile wizard from the same markup contract:
 *
 * <div data-step-wizard>
 *   <div data-step-progress>
 *     <div class="step-progress-item" data-step-item="1">...</div>
 *     <div class="step-progress-line"></div>
 *     <div class="step-progress-item" data-step-item="2">...</div>
 *     ...
 *   </div>
 *   <fieldset data-step-panel data-step="1"> ... <button data-step-next>Next</button></fieldset>
 *   <fieldset data-step-panel data-step="2" hidden> <button data-step-prev>Back</button> ...</fieldset>
 *   ...
 * </div>
 *
 * The final panel's advance button should be a normal type="submit" button
 * (no data-step-next) so the browser submits the whole form.
 */
(function () {
    'use strict';

    function initWizard(root) {
        var panels = Array.prototype.slice.call(root.querySelectorAll('[data-step-panel]'));
        if (!panels.length) return;

        panels.sort(function (a, b) {
            return parseInt(a.getAttribute('data-step'), 10) - parseInt(b.getAttribute('data-step'), 10);
        });

        var progressItems = Array.prototype.slice.call(root.querySelectorAll('[data-step-item]'));
        var progressLines = Array.prototype.slice.call(root.querySelectorAll('.step-progress-line'));

        var startStep = parseInt(root.getAttribute('data-start-step') || '', 10);
        var current = panels.some(function (p) { return parseInt(p.getAttribute('data-step'), 10) === startStep; })
            ? startStep
            : parseInt(panels[0].getAttribute('data-step'), 10);

        function showStep(stepNum) {
            current = stepNum;

            panels.forEach(function (panel) {
                var isMatch = parseInt(panel.getAttribute('data-step'), 10) === stepNum;
                panel.hidden = !isMatch;
            });

            progressItems.forEach(function (item) {
                var n = parseInt(item.getAttribute('data-step-item'), 10);
                item.classList.toggle('is-active', n === stepNum);
                item.classList.toggle('is-done', n < stepNum);
            });
            progressLines.forEach(function (line, idx) {
                line.classList.toggle('is-done', (idx + 1) < stepNum);
            });

            var activePanel = panels.filter(function (p) { return parseInt(p.getAttribute('data-step'), 10) === stepNum; })[0];
            if (activePanel) {
                var firstField = activePanel.querySelector('input, select, textarea');
                if (firstField && typeof firstField.focus === 'function') {
                    firstField.focus({ preventScroll: true });
                }
            }
        }

        function panelIsValid(panel) {
            var fields = Array.prototype.slice.call(panel.querySelectorAll('input, select, textarea'));
            var allValid = true;
            fields.forEach(function (field) {
                if (!field.checkValidity()) {
                    allValid = false;
                }
            });
            if (!allValid) {
                var firstInvalid = fields.filter(function (f) { return !f.checkValidity(); })[0];
                if (firstInvalid) firstInvalid.reportValidity();
            }
            return allValid;
        }

        root.addEventListener('click', function (e) {
            var nextBtn = e.target.closest('[data-step-next]');
            if (nextBtn) {
                var currentPanel = panels.filter(function (p) { return parseInt(p.getAttribute('data-step'), 10) === current; })[0];
                if (currentPanel && !panelIsValid(currentPanel)) {
                    e.preventDefault();
                    return;
                }
                e.preventDefault();
                var idx = panels.indexOf(currentPanel);
                if (idx > -1 && idx + 1 < panels.length) {
                    showStep(parseInt(panels[idx + 1].getAttribute('data-step'), 10));
                }
                return;
            }

            var prevBtn = e.target.closest('[data-step-prev]');
            if (prevBtn) {
                e.preventDefault();
                var currentPanel2 = panels.filter(function (p) { return parseInt(p.getAttribute('data-step'), 10) === current; })[0];
                var idx2 = panels.indexOf(currentPanel2);
                if (idx2 > 0) {
                    showStep(parseInt(panels[idx2 - 1].getAttribute('data-step'), 10));
                }
            }
        });

        showStep(current);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var wizards = document.querySelectorAll('[data-step-wizard]');
        wizards.forEach ? wizards.forEach(initWizard) : Array.prototype.forEach.call(wizards, initWizard);
    });
})();
