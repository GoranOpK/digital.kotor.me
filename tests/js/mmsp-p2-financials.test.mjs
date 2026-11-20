import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/mmsp-p2-financials.js', import.meta.url), 'utf8');
const sandbox = {};
vm.createContext(sandbox);
vm.runInContext(source, sandbox);
const financials = sandbox.MmspP2Financials;

test('sums all five expense amounts and subtracts own contribution', () => {
    const result = financials.recalculate(
        ['10.50', '20,25', '1', '0.01', '100'],
        '31.76',
    );

    assert.equal(result.total, '131.76');
    assert.equal(result.subsidy, '100.00');
});

test('changing one expense recalculates both totals', () => {
    const before = financials.recalculate(['10.00', '10.00', '10.00', '10.00', '10.00'], '15.00');
    const after = financials.recalculate(['10.00', '12.50', '10.00', '10.00', '10.00'], '15.00');

    assert.equal(before.total, '50.00');
    assert.equal(before.subsidy, '35.00');
    assert.equal(after.total, '52.50');
    assert.equal(after.subsidy, '37.50');
});

test('changing own contribution changes only the subsidy', () => {
    const amounts = ['40.00', '10.00', '', '', ''];
    const lower = financials.recalculate(amounts, '5,5');
    const higher = financials.recalculate(amounts, '20.00');

    assert.equal(lower.total, '50.00');
    assert.equal(lower.subsidy, '44.50');
    assert.equal(higher.total, '50.00');
    assert.equal(higher.subsidy, '30.00');
});

test('invalid input does not produce a partial or rounded sum', () => {
    const invalidAmount = financials.recalculate(['10.00', '10.555', '1.00', '', ''], '1.00');
    const invalidText = financials.recalculate(['10.00', 'abc', '', '', ''], '1.00');
    const invalidOwn = financials.recalculate(['10.00', '', '', '', ''], '1.2.3');
    assert.equal(invalidAmount.total, '');
    assert.equal(invalidAmount.subsidy, '');
    assert.equal(invalidText.total, '');
    assert.equal(invalidText.subsidy, '');
    assert.equal(invalidOwn.total, '10.00');
    assert.equal(invalidOwn.subsidy, '');
});

test('own contribution above the total shows the negative subsidy instead of hiding it', () => {
    const result = financials.recalculate(['10.00', '5.00', '', '', ''], '20.00');
    assert.equal(result.total, '15.00');
    assert.equal(result.subsidy, '-5.00');
});
