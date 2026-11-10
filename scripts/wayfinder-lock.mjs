import { mkdirSync, readFileSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

/**
 * One writer or reader of the generated Wayfinder files at a time.
 *
 * `wayfinder:generate` deletes resources/js/routes and resources/js/actions and writes every file in two steps
 * (content, then imports prepended), so anything that reads them meanwhile (vue-tsc, Vite) can see a missing module or a
 * half-written file. Generation and type checking therefore both run while holding this lock. mkdir is atomic, so it
 * works across the Vite dev server, `npm run build`, `npm run types` and manual runs.
 */
export const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const lockDir = join(root, 'storage', 'framework', 'wayfinder.lock');
const marker = join(root, 'resources', 'js', 'wayfinder', '.generated.json');
const STALE_MS = 10 * 60 * 1000;
const WAIT_MS = 180 * 1000;

const alive = (pid) => {
    try {
        process.kill(pid, 0);
        return true;
    } catch (error) {
        return error.code === 'EPERM';
    }
};

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

export async function acquire() {
    mkdirSync(dirname(lockDir), { recursive: true });
    const started = Date.now();

    for (;;) {
        try {
            mkdirSync(lockDir);
            writeFileSync(join(lockDir, 'pid'), String(process.pid));
            return;
        } catch (error) {
            if (error.code !== 'EEXIST') throw error;
        }

        let holder = NaN;
        try {
            holder = Number(readFileSync(join(lockDir, 'pid'), 'utf8'));
        } catch {
            /* the holder is between mkdir and writing its pid */
        }
        const age = Date.now() - statSync(lockDir).mtimeMs;

        if ((!Number.isNaN(holder) && !alive(holder)) || age > STALE_MS) {
            rmSync(lockDir, { recursive: true, force: true }); // a crashed process left it behind
            continue;
        }
        if (Date.now() - started > WAIT_MS) throw new Error('Timed out waiting for another Wayfinder generation or type check to finish.');
        await sleep(200);
    }
}

export function release() {
    rmSync(lockDir, { recursive: true, force: true });
}

export const markerPath = marker;
