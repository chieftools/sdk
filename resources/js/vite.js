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

  If the SDK picks up new CJS-only peer deps in the future, extend the
  `include` list here so every consuming app inherits the fix.
*/
export default function chiefSdk() {
    return {
        name: '@chieftools/sdk:vite',
        config() {
            return {
                optimizeDeps: {
                    exclude: ['@chieftools/sdk'],
                    include: ['clipboard'],
                },
            };
        },
    };
}
