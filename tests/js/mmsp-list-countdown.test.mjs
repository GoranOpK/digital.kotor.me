import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/mmsp-list-countdown.js', import.meta.url), 'utf8');
const context = {};
vm.createContext(context);
vm.runInContext(source, context);
const countdown = context.MmspListCountdown;

function seconds(days, hours, minutes, extraSeconds = 0) {
    return ((days * 24 * 60 * 60) + (hours * 60 * 60) + (minutes * 60) + extraSeconds) * 1000;
}

test('uses one, paucal, and plural forms for days, hours, and minutes', () => {
    assert.equal(countdown.format(seconds(1, 0, 0)), '1 dan');
    assert.equal(countdown.format(seconds(2, 0, 0)), '2 dana');
    assert.equal(countdown.format(seconds(4, 0, 0)), '4 dana');
    assert.equal(countdown.format(seconds(5, 0, 0)), '5 dana');
    assert.equal(countdown.format(seconds(11, 0, 0)), '11 dana');
    assert.equal(countdown.format(seconds(21, 0, 0)), '21 dan');
    assert.equal(countdown.format(seconds(22, 0, 0)), '22 dana');

    assert.equal(countdown.format(seconds(0, 1, 0)), '1 sat');
    assert.equal(countdown.format(seconds(0, 2, 0)), '2 sata');
    assert.equal(countdown.format(seconds(0, 5, 0)), '5 sati');
    assert.equal(countdown.format(seconds(0, 11, 0)), '11 sati');
    assert.equal(countdown.format(seconds(0, 12, 0)), '12 sati');
    assert.equal(countdown.format(seconds(0, 21, 0)), '21 sat');
    assert.equal(countdown.format(seconds(0, 22, 0)), '22 sata');

    assert.equal(countdown.format(seconds(0, 0, 1)), '1 minut');
    assert.equal(countdown.format(seconds(0, 0, 2)), '2 minuta');
    assert.equal(countdown.format(seconds(0, 0, 5)), '5 minuta');
    assert.equal(countdown.format(seconds(0, 0, 11)), '11 minuta');
    assert.equal(countdown.format(seconds(0, 0, 21)), '21 minut');
    assert.equal(countdown.format(seconds(0, 0, 22)), '22 minuta');
});

test('joins the remaining parts and keeps an expired deadline unchanged', () => {
    assert.equal(countdown.format(seconds(1, 2, 1)), '1 dan, 2 sata, 1 minut');
    assert.equal(countdown.format(seconds(21, 0, 22)), '21 dan, 22 minuta');
    assert.equal(countdown.format(seconds(0, 0, 0, 1)), '1 sekund');
    assert.equal(countdown.format(seconds(0, 0, 0, 22)), '22 sekunda');
    assert.equal(countdown.format(seconds(0, 0, 0, 11)), '11 sekundi');
    assert.equal(countdown.format(-1000), 'Rok je istekao');
});
