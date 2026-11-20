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

    function bind(scope) {
        var rootElement = scope || document;
        var amountInputs = rootElement.querySelectorAll('[data-mmsp-expense-amount]');
        var totalInput = rootElement.querySelector('#total_investment');
        var ownInput = rootElement.querySelector('#own_contribution');
        var subsidyInput = rootElement.querySelector('#expected_subsidy');
        var fieldset = rootElement.querySelector('form[action*="obrazac-p2"] fieldset, form fieldset');

        if (!totalInput || !ownInput || !subsidyInput || (fieldset && fieldset.disabled)) {
            return;
        }

        function update() {
            var amounts = [];
            amountInputs.forEach(function (input) {
                amounts.push(input.value);
            });
            var result = recalculate(amounts, ownInput.value);
            totalInput.value = result.total;
            subsidyInput.value = result.subsidy;
        }

        amountInputs.forEach(function (input) {
            input.addEventListener('input', update);
            input.addEventListener('change', update);
        });
        ownInput.addEventListener('input', update);
        ownInput.addEventListener('change', update);
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
