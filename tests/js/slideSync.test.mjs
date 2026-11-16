import assert from 'node:assert/strict';
import { afterEach, beforeEach, mock, test } from 'node:test';
import { createSlideSync, nextIndex } from '../../resources/js/lib/slideSync.ts';

const json = (body, ok = true) => Promise.resolve({ ok, status: ok ? 200 : 500, json: () => Promise.resolve(body) });
const flush = async () => { for (let i = 0; i < 10; i++) await Promise.resolve(); };

let sync;
beforeEach(() => mock.timers.enable({ apis: ['setTimeout'] }));
afterEach(() => { sync?.stop(); mock.timers.reset(); });

function setup(routes, version = 'v1') {
    const calls = [];
    const updates = [];
    const fetchImpl = (url) => { calls.push(url); return routes(url); };
    sync = createSlideSync({ version, fetchImpl, onUpdate: (d) => updates.push(d) });
    sync.start();
    return { calls, updates };
}
const tick = async (ms) => { mock.timers.tick(ms); await flush(); };

test('polls every 60s, version only, no slide download when unchanged', async () => {
    const { calls, updates } = setup(() => json({ version: 'v1' }));
    await tick(59_999); assert.equal(calls.length, 0);
    await tick(1); assert.deepEqual(calls, ['/slides/version']);
    await tick(60_000); assert.equal(calls.length, 2);
    assert.equal(updates.length, 0);
});

test('changed version downloads data once and applies it', async () => {
    const { calls, updates } = setup((u) => json(u.endsWith('version') ? { version: 'v2' } : { version: 'v2', images: ['a', 'b'] }));
    await tick(60_000);
    assert.deepEqual(calls, ['/slides/version', '/slides/data']);
    assert.deepEqual(updates, [{ version: 'v2', images: ['a', 'b'] }]);
    await tick(60_000); assert.equal(calls.length, 3); // v2 now known: version request only
});

test('failures and invalid responses keep data, back off, and recover to 60s', async () => {
    let mode = 'fail';
    const { calls, updates } = setup(() => (mode === 'fail' ? Promise.reject(new Error('offline')) : mode === 'bad' ? json({ nope: 1 }) : json({ version: 'v1' })));
    await tick(60_000); assert.equal(calls.length, 1);
    await tick(60_000); assert.equal(calls.length, 1); // backed off to 120s
    await tick(60_000); assert.equal(calls.length, 2);
    mode = 'bad';
    await tick(240_000); assert.equal(calls.length, 3);
    mode = 'ok';
    await tick(480_000); assert.equal(calls.length, 4);
    await tick(60_000); assert.equal(calls.length, 5); // normal interval again
    assert.equal(updates.length, 0);
});

test('empty or malformed slide list never replaces slides', async () => {
    const { updates } = setup((u) => json(u.endsWith('version') ? { version: 'v2' } : { version: 'v2', images: [] }));
    await tick(60_000);
    assert.equal(updates.length, 0);
});

test('no overlapping requests and stop clears the timer', async () => {
    const { calls } = setup(() => new Promise(() => {}));
    await tick(600_000); assert.equal(calls.length, 1); // hung request blocks further polling
    sync.stop();
});

test('stop cancels pending polling', async () => {
    const { calls } = setup(() => json({ version: 'v1' }));
    sync.stop();
    await tick(300_000);
    assert.equal(calls.length, 0);
});

test('nextIndex keeps current slide, handles removal, add and reorder', () => {
    assert.equal(nextIndex('b', 1, ['x', 'a', 'b']), 2); // reorder/add before
    assert.equal(nextIndex('b', 1, ['a', 'b', 'c']), 1);
    assert.equal(nextIndex('b', 1, ['a', 'c', 'd']), 1); // removed: next slide takes its place
    assert.equal(nextIndex('c', 2, ['a', 'b']), 1); // removed at end: clamp
});
