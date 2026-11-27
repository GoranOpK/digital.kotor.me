import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/mmsp-p2-financials.js', import.meta.url), 'utf8');
const sandbox = {};
vm.createContext(sandbox);
vm.runInContext(source, sandbox);
const financials = sandbox.MmspP2Financials;

test('authentic local products take 80 percent, floored to the cent', () => {
    const result = financials.recalculate(['5000'], 80);
    assert.equal(result.total, '5000.00');
    assert.equal(result.own, '1000.00');
    assert.equal(result.subsidy, '4000.00');

    const cents = financials.recalculate(['10.01'], 80);
    assert.equal(cents.total, '10.01');
    assert.equal(cents.own, '2.01');
    assert.equal(cents.subsidy, '8.00');
});

test('innovative products and digitalization take 50 percent', () => {
    const innovative = financials.recalculate(['2500', '2500'], 50);
    const digital = financials.recalculate(['3333.33', '1666.67'], 50);

    assert.equal(innovative.total, '5000.00');
    assert.equal(innovative.own, '2500.00');
    assert.equal(innovative.subsidy, '2500.00');
    assert.equal(digital.total, '5000.00');
    assert.equal(digital.own, '2500.00');
    assert.equal(digital.subsidy, '2500.00');
});

test('adding or changing an amount recalculates every field from the percentage', () => {
    const before = financials.recalculate(['10.00', '10.00', '10.00', '10.00', '10.00'], 50);
    const after = financials.recalculate(['10.00', '12.50', '10.00', '10.00', '10.00'], 50);

    assert.equal(before.total, '50.00');
    assert.equal(before.own, '25.00');
    assert.equal(before.subsidy, '25.00');
    assert.equal(after.total, '52.50');
    assert.equal(after.own, '26.25');
    assert.equal(after.subsidy, '26.25');
});

test('invalid input does not produce a partial sum', () => {
    const invalidAmount = financials.recalculate(['10.00', '10.555', '1.00', '', ''], 50);
    const invalidText = financials.recalculate(['10.00', 'abc', '', '', ''], 80);
    const missingPercent = financials.recalculate(['10.00', '5,5'], null);

    assert.equal(invalidAmount.total, '');
    assert.equal(invalidAmount.own, '');
    assert.equal(invalidAmount.subsidy, '');
    assert.equal(invalidText.total, '');
    assert.equal(missingPercent.total, '15.50');
    assert.equal(missingPercent.own, '');
    assert.equal(missingPercent.subsidy, '');
});

test('removing amounts leaves the fixed percentage of the remaining total', () => {
    const result = financials.recalculate(['10.00', '', '5.00'], 80);
    assert.equal(result.total, '15.00');
    assert.equal(result.own, '3.00');
    assert.equal(result.subsidy, '12.00');
});
