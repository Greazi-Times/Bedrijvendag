import { spawn } from 'node:child_process';
import { mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { dirname } from 'node:path';
import { acquire, markerPath, release, root } from './wayfinder-lock.mjs';

/**
 * Runs `php artisan wayfinder:generate` under the shared lock. The completion marker is removed first and written only
 * after a successful run, so an interrupted generation is noticed (the type check regenerates when the marker is absent).
 * Used by the Vite plugin (vite.config.ts) and by `npm run wayfinder`.
 */
export function generate(args = process.argv.slice(2)) {
    rmSync(markerPath, { force: true });

    return new Promise((resolve) => {
        const child = spawn('php', ['artisan', 'wayfinder:generate', ...args], { cwd: root, stdio: 'inherit' });
        child.on('exit', (code) => {
            if (code === 0) {
                mkdirSync(dirname(markerPath), { recursive: true });
                writeFileSync(markerPath, JSON.stringify({ generatedAt: new Date().toISOString(), args }));
            }
            resolve(code ?? 1);
        });
    });
}

if (import.meta.url === `file://${process.argv[1]}`) {
    await acquire();
    let code = 1;
    try {
        code = await generate();
    } finally {
        release();
    }
    process.exit(code);
}
