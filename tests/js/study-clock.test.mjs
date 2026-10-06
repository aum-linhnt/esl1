import { test } from 'node:test';
import assert from 'node:assert/strict';
import { StudyClock } from '../../resources/js/study-clock.js';

test('visible focused study counts; hidden and unfocused tabs pause', () => {
    const clock = new StudyClock(0);
    assert.equal(clock.step(1000, true, true), 1);
    assert.equal(clock.step(2000, false, true), 1);
    assert.equal(clock.step(3000, true, false), 1);
    assert.equal(clock.step(4000, true, true), 2);
});
test('idle pages pause after two minutes and resume on interaction', () => {
    const clock = new StudyClock(0);
    for (let time = 1000; time <= 120000; time += 1000) clock.step(time, true, true);
    assert.equal(clock.step(121000, true, true), 120);
    clock.interact(121000);
    assert.equal(clock.step(122000, true, true), 121);
});
test('suspended browser gaps are bounded and media requires a visible focused page', () => {
    const clock = new StudyClock(0);
    assert.equal(clock.step(200000, true, true, true), 5);
    assert.equal(clock.step(201000, false, true, true), 5);
    assert.equal(clock.step(202000, true, false, true), 5);
    assert.equal(clock.step(203000, true, true, true), 6);
});
