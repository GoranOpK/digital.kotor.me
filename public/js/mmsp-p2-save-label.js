(function (root) {
    var AMOUNT_PATTERN = /^\d{1,12}(?:[.,]\d{1,2})?$/;

    function parseAmount(raw) {
        var value = String(raw == null ? '' : raw).trim();
        if (value === '') {
            return { empty: true };
        }
        if (!AMOUNT_PATTERN.test(value)) {
            return { invalid: true };
        }

        var parts = value.replace(',', '.').split('.');
        var fraction = (parts[1] || '').padEnd(2, '0');

        return { cents: BigInt(parts[0]) * 100n + BigInt(fraction) };
    }

    function fieldValue(form, name) {
        var field = form.elements.namedItem(name);
        if (!field) {
            return '';
        }

        return String(field.value || '').trim();
    }

    function expenseLines(form) {
        var rows = {};
        var inputs = form.querySelectorAll('[name^="expense_lines["]');
        var index;

        for (index = 0; index < inputs.length; index += 1) {
            var match = inputs[index].name.match(/^expense_lines\[(\d+)\]\[(cost_type|description|amount)\]$/);
            if (!match) {
                continue;
            }
            if (!rows[match[1]]) {
                rows[match[1]] = { cost_type: '', description: '', amount: '' };
            }
            rows[match[1]][match[2]] = String(inputs[index].value || '').trim();
        }

        var lines = [];
        var keys = Object.keys(rows).sort(function (left, right) {
            return Number(left) - Number(right);
        });

        for (index = 0; index < keys.length; index += 1) {
            var row = rows[keys[index]];
            if (row.cost_type === '' && row.description === '' && row.amount === '') {
                continue;
            }
            if (row.cost_type === '' || row.description === '') {
                return null;
            }

            var amount = parseAmount(row.amount);
            if (amount.invalid || amount.empty || amount.cents <= 0n) {
                return null;
            }
            lines.push(amount.cents);
        }

        return lines;
    }

    function p2ReadyForSave(form, subsidyCodes, narrativeFields) {
        var subsidy = fieldValue(form, 'subsidy_type');
        if (subsidyCodes.indexOf(subsidy) === -1) {
            return false;
        }

        var lines = expenseLines(form);
        if (lines === null || lines.length === 0) {
            return false;
        }

        var total = parseAmount(fieldValue(form, 'total_investment'));
        var own = parseAmount(fieldValue(form, 'own_contribution'));
        var expected = parseAmount(fieldValue(form, 'expected_subsidy'));
        if (total.invalid || total.empty || total.cents <= 0n) {
            return false;
        }
        if (own.invalid || own.empty || own.cents < 0n || own.cents > total.cents) {
            return false;
        }
        if (expected.invalid || expected.empty || expected.cents <= 0n || expected.cents > total.cents) {
            return false;
        }

        var sum = 0n;
        var index;
        for (index = 0; index < lines.length; index += 1) {
            sum += lines[index];
        }
        if (sum !== total.cents) {
            return false;
        }

        for (index = 0; index < narrativeFields.length; index += 1) {
            if (fieldValue(form, 'narrative_sections[' + narrativeFields[index] + ']') === '') {
                return false;
            }
        }

        return true;
    }

    function bind(scope) {
        var rootElement = scope || document;
        var button = rootElement.querySelector('#p2-save-button');
        var form = rootElement.querySelector('#p2-save-button')
            ? rootElement.querySelector('#p2-save-button').form
            : null;

        if (!button || !form) {
            return;
        }

        var subsidyCodes = [];
        var narrativeFields = [];
        try {
            subsidyCodes = JSON.parse(button.dataset.subsidyCodes || '[]');
            narrativeFields = JSON.parse(button.dataset.narrativeFields || '[]');
        } catch (error) {
            subsidyCodes = [];
            narrativeFields = [];
        }

        function apply() {
            var ready = p2ReadyForSave(form, subsidyCodes, narrativeFields);
            button.textContent = ready ? button.dataset.labelPlan : button.dataset.labelDraft;
            button.classList.toggle('mmsp-p2-save-ready', ready);
            button.classList.toggle('mmsp-p2-save-draft', !ready);
        }

        form.addEventListener('input', apply);
        form.addEventListener('change', apply);
        apply();
    }

    root.MmspP2SaveLabel = {
        parseAmount: parseAmount,
        p2ReadyForSave: p2ReadyForSave,
        bind: bind,
    };

    if (typeof document !== 'undefined') {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                bind(document);
            });
        } else {
            bind(document);
        }
    }
})(typeof globalThis !== 'undefined' ? globalThis : window);
