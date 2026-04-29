/*
  Vite plugin contributing the configuration needed to consume
  `@chieftools/sdk` from a Laravel/Vite application.

  Usage in vite.config.mjs:

      import chiefSdk from '@chieftools/sdk/vite';

      export default defineConfig({
          plugins: [
              laravel({...}),
              tailwindcss(),
              chiefSdk(),
              ...
          ],
      });

  What it does:

   - Excludes `@chieftools/sdk` from Vite's dep pre-bundling so SDK source
     files vendored via yarn's `file:` protocol (which copies, not
     symlinks) are picked up on every page reload after
     `yarn install --force` — no Vite restart required.

   - Force-includes the SDK's CJS-only peer deps so Vite still pre-bundles
     those into ESM wrappers with synthetic default exports. Without this
     the SDK's `import Clipboard from 'clipboard'` would fail because
     `clipboard` ships only `dist/clipboard.js` (no `module` field) and the
     browser cannot synthesize a default export from raw CommonJS.

   - Copies the shared Sentry replay worker from the SDK package to
     `<projectRoot>/public/js/sentry/replay-worker.min.js` on every
     dev server start and production build, so apps don't need to
     vendor the file themselves. The path matches the `workerUrl`
     configured in `@chieftools/sdk/sentry`.

  If the SDK picks up new CJS-only peer deps in the future, extend the
  `include` list here so every consuming app inherits the fix.
*/
import {copyFileSync, existsSync, mkdirSync, statSync} from 'node:fs';
import {dirname, join, resolve} from 'node:path';
import {fileURLToPath} from 'node:url';

const sdkRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const replayWorkerSource = join(sdkRoot, 'files', 'sentry', 'replay-worker.min.js');
const replayWorkerRelativeDest = join('public', 'js', 'sentry', 'replay-worker.min.js');

function syncReplayWorker(projectRoot) {
    if (!existsSync(replayWorkerSource)) {
        return;
    }

    const dest = join(projectRoot, replayWorkerRelativeDest);

    if (existsSync(dest)) {
        const sourceStat = statSync(replayWorkerSource);
        const destStat = statSync(dest);

        if (sourceStat.size === destStat.size && sourceStat.mtimeMs <= destStat.mtimeMs) {
            return;
        }
    }

    mkdirSync(dirname(dest), {recursive: true});
    copyFileSync(replayWorkerSource, dest);
}

export default function chiefSdk() {
    let projectRoot = process.cwd();

    return {
        name: '@chieftools/sdk:vite',
        config(_, env) {
            return {
                optimizeDeps: {
                    exclude: ['@chieftools/sdk'],
                    include: ['clipboard'],
                },
            };
        },
        configResolved(resolvedConfig) {
            projectRoot = resolvedConfig.root;
        },
        buildStart() {
            syncReplayWorker(projectRoot);
        },
    };
}
