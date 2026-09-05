/**
 * Skill chip picker. Renders clickable suggestion chips plus a free-text
 * "add skill" input, and keeps a hidden <input> in sync as a comma-joined
 * string — the same format the server's Skill::parseList()/syncApplicantSkills()
 * already expects, so no backend change is needed to consume this.
 *
 * Markup contract:
 * <div data-skill-chips data-suggestions='["JavaScript","SQL"]'>
 *   <input type="hidden" data-chip-value name="skills" value="HTML, CSS">
 *   <div data-chip-suggestions></div>
 *   <input type="text" data-chip-input>
 *   <button type="button" data-chip-add>Add skill</button>
 *   <div data-chip-selected></div>
 * </div>
 */
(function () {
    'use strict';

    var DEFAULT_SUGGESTIONS = [
        'JavaScript', 'HTML & CSS', 'React', 'Figma', 'Communication',
        'Project Management', 'Microsoft Excel', 'Data Analysis', 'SQL',
        'Customer Service', 'Digital Marketing', 'Graphic Design'
    ];

    function parseList(value) {
        var seen = {};
        var result = [];
        (value || '').split(',').forEach(function (raw) {
            var name = raw.replace(/\s+/g, ' ').trim();
            if (!name || seen[name.toLowerCase()]) return;
            seen[name.toLowerCase()] = true;
            result.push(name);
        });
        return result;
    }

    function initPicker(root) {
        var hiddenInput = root.querySelector('[data-chip-value]');
        var suggestionsEl = root.querySelector('[data-chip-suggestions]');
        var customInput = root.querySelector('[data-chip-input]');
        var addBtn = root.querySelector('[data-chip-add]');
        var selectedEl = root.querySelector('[data-chip-selected]');
        if (!hiddenInput || !suggestionsEl || !selectedEl) return;

        var suggestions = DEFAULT_SUGGESTIONS;
        var suggestionsAttr = root.getAttribute('data-suggestions');
        if (suggestionsAttr) {
            try {
                var parsed = JSON.parse(suggestionsAttr);
                if (Array.isArray(parsed) && parsed.length) suggestions = parsed;
            } catch (e) { /* fall back to defaults */ }
        }

        var selected = parseList(hiddenInput.value);

        function hasSelected(name) {
            return selected.some(function (s) { return s.toLowerCase() === name.toLowerCase(); });
        }

        function toggleSuggestion(name) {
            if (hasSelected(name)) {
                selected = selected.filter(function (s) { return s.toLowerCase() !== name.toLowerCase(); });
            } else {
                selected.push(name);
            }
            render();
        }

        function addCustom() {
            if (!customInput) return;
            var name = customInput.value.replace(/\s+/g, ' ').trim();
            if (!name || hasSelected(name)) {
                customInput.value = '';
                return;
            }
            selected.push(name);
            customInput.value = '';
            render();
        }

        function removeSelected(name) {
            selected = selected.filter(function (s) { return s.toLowerCase() !== name.toLowerCase(); });
            render();
        }

        function render() {
            hiddenInput.value = selected.join(', ');

            suggestionsEl.innerHTML = '';
            suggestions.forEach(function (name) {
                var chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'chip' + (hasSelected(name) ? ' is-selected' : '');
                chip.textContent = name;
                chip.addEventListener('click', function () { toggleSuggestion(name); });
                suggestionsEl.appendChild(chip);
            });

            selectedEl.innerHTML = '';
            selected.forEach(function (name) {
                var pill = document.createElement('span');
                pill.className = 'chip-selected';

                var label = document.createElement('span');
                label.textContent = name;
                pill.appendChild(label);

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'chip-remove';
                remove.setAttribute('aria-label', 'Remove ' + name);
                remove.textContent = '×';
                remove.addEventListener('click', function () { removeSelected(name); });
                pill.appendChild(remove);

                selectedEl.appendChild(pill);
            });
        }

        if (addBtn) {
            addBtn.addEventListener('click', addCustom);
        }
        if (customInput) {
            customInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addCustom();
                }
            });
        }

        render();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var pickers = document.querySelectorAll('[data-skill-chips]');
        Array.prototype.forEach.call(pickers, initPicker);
    });
})();
