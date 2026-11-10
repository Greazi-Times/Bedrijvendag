import { spawn } from 'node:child_process';
import { readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { generate } from './wayfinder-generate.mjs';
import { acquire, markerPath, release, root } from './wayfinder-lock.mjs';

/**
 * Deterministic type check: holds the Wayfinder lock for the whole run, so generation (from the Vite dev server, a build or
 * another terminal) cannot rewrite resources/js/routes or actions while vue-tsc reads them. Generated files are only
 * rebuilt when the completion marker is missing (an interrupted run) or a route/controller source is newer than it.
 * Nothing is excluded from checking and no error is suppressed. `--regenerate` forces generation.
 */
const newest = (dir, pick) => {
    let latest = 0;
    for (const entry of readdirSync(dir, { withFileTypes: true })) {
        const path = join(dir, entry.name);
        if (entry.isDirectory()) latest = Math.max(latest, newest(path, pick));
        else if (pick(path)) latest = Math.max(latest, statSync(path).mtimeMs);
    }
    return latest;
};

const needsGeneration = () => {
    if (process.argv.includes('--regenerate')) return true;
    let marker;
    try {
        marker = statSync(markerPath).mtimeMs;
    } catch {
        return true;
    }
    const php = (path) => path.endsWith('.php');
    return (
        Math.max(
            newest(join(root, 'routes'), php),
            newest(join(root, 'app'), (path) => php(path) && path.includes('/Http/')),
        ) > marker
    );
};

await acquire();
let code = 1;
try {
    if (needsGeneration()) {
        console.log('Generating Wayfinder routes and actions first...');
        code = await generate(['--with-form']);
        if (code !== 0) {
            console.error('Wayfinder generation failed; not type checking.');
            process.exit(code);
        }
    }
    code = await new Promise((resolve) => {
        spawn(join(root, 'node_modules', '.bin', 'vue-tsc'), ['--noEmit'], { cwd: root, stdio: 'inherit' }).on('exit', (c) => resolve(c ?? 1));
    });
} finally {
    release();
}
process.exit(code);
