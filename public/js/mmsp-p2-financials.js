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

    function formatCents(cents) {
        var negative = cents < 0n;
        var absolute = negative ? -cents : cents;
        var whole = (absolute / 100n).toString();
        var fraction = (absolute % 100n).toString().padStart(2, '0');

        return (negative ? '-' : '') + whole + '.' + fraction;
    }

    function recalculate(amounts, ownRaw) {
        var total = 0n;
        var index;

        for (index = 0; index < amounts.length; index += 1) {
            var parsed = parseAmount(amounts[index]);
            if (parsed.invalid) {
                return { total: '', subsidy: '' };
            }
            if (!parsed.empty) {
                total += parsed.cents;
            }
        }

        var own = parseAmount(ownRaw);
        if (own.invalid || own.empty) {
            return { total: formatCents(total), subsidy: '' };
        }

        return {
            total: formatCents(total),
            subsidy: formatCents(total - own.cents),
        };
    }

    function rowIsEmpty(row) {
        var inputs = row.querySelectorAll('input');
        var index;

        for (index = 0; index < inputs.length; index += 1) {
            if (String(inputs[index].value || '').trim() !== '') {
                return false;
            }
        }

        return true;
    }

    function markEmptyRows(table) {
        if (!table) {
            return;
        }
        table.querySelectorAll('tbody tr').forEach(function (row) {
            row.classList.toggle('mmsp-p2-expense-print-hide', rowIsEmpty(row));
        });
    }

    function clearPrintMarks(table) {
        if (!table) {
            return;
        }
        table.querySelectorAll('tbody tr').forEach(function (row) {
            row.classList.remove('mmsp-p2-expense-print-hide');
        });
    }

    function addExpenseRow(table, button) {
        var body = table.querySelector('tbody');
        var maxLines = Number(button.getAttribute('data-mmsp-max-expense-lines') || '50');
        var index = body.querySelectorAll('tr').length;
        var row;

        if (index >= maxLines) {
            return;
        }

        row = document.createElement('tr');
        row.innerHTML = ''
            + '<td><input class="mmsp-form-control" type="text" name="expense_lines[' + index + '][cost_type]" value=""></td>'
            + '<td><input class="mmsp-form-control" type="text" name="expense_lines[' + index + '][description]" value=""></td>'
            + '<td><input class="mmsp-form-control" type="text" inputmode="decimal" data-mmsp-expense-amount name="expense_lines[' + index + '][amount]" value=""></td>';
        body.appendChild(row);
    }

    function bind(scope) {
        var rootElement = scope || document;
        var table = rootElement.querySelector('[data-mmsp-expense-table]');
        var addButton = rootElement.querySelector('[data-mmsp-add-expense-row]');
        var totalInput = rootElement.querySelector('#total_investment');
        var ownInput = rootElement.querySelector('#own_contribution');
        var subsidyInput = rootElement.querySelector('#expected_subsidy');
        var fieldset = rootElement.querySelector('form[action*="obrazac-p2"] fieldset, form fieldset');

        if (!totalInput || !ownInput || !subsidyInput || (fieldset && fieldset.disabled)) {
            return;
        }

        function update() {
            var amounts = [];
            rootElement.querySelectorAll('[data-mmsp-expense-amount]').forEach(function (input) {
                amounts.push(input.value);
            });
            var result = recalculate(amounts, ownInput.value);
            totalInput.value = result.total;
            subsidyInput.value = result.subsidy;
        }

        if (table) {
            table.addEventListener('input', function (event) {
                if (event.target && event.target.matches && event.target.matches('[data-mmsp-expense-amount]')) {
                    update();
                }
            });
            table.addEventListener('change', function (event) {
                if (event.target && event.target.matches && event.target.matches('[data-mmsp-expense-amount]')) {
                    update();
                }
            });
        }
        ownInput.addEventListener('input', update);
        ownInput.addEventListener('change', update);
        if (addButton && table) {
            addButton.addEventListener('click', function () {
                addExpenseRow(table, addButton);
                update();
            });
        }
        window.addEventListener('beforeprint', function () {
            markEmptyRows(table);
        });
        window.addEventListener('afterprint', function () {
            clearPrintMarks(table);
        });
        update();
    }

    root.MmspP2Financials = {
        parseAmount: parseAmount,
        recalculate: recalculate,
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
