/*
  Builds the self-hosted GraphiQL playground into `files/graphiql`.

  Run `yarn build:graphiql` after upgrading any of the GraphiQL packages and
  commit the output. Assets use a relative base so chunks and Monaco workers
  resolve from wherever the directory is served (`public/js/graphiql`).
*/
import {dirname, resolve} from 'node:path';
import {fileURLToPath} from 'node:url';
import {defineConfig} from 'vite';

const root = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    root,
    base: './',
    publicDir: false,
    define: {
        'process.env.NODE_ENV': JSON.stringify('production'),
    },
    build: {
        outDir: resolve(root, '../../../files/graphiql'),
        emptyOutDir: true,
        manifest: 'manifest.json',
        chunkSizeWarningLimit: 5000,
        rolldownOptions: {
            input: resolve(root, 'index.js'),
            onwarn(warning, warn) {
                // React Server Components directives are meaningless in this client-only bundle
                if (warning.code !== 'MODULE_LEVEL_DIRECTIVE') {
                    warn(warning);
                }
            },
            output: {
                entryFileNames: 'graphiql-[hash].js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
    worker: {
        format: 'es',
        rolldownOptions: {
            output: {
                entryFileNames: 'workers/[name]-[hash].js',
                chunkFileNames: 'workers/chunks/[name]-[hash].js',
                assetFileNames: 'workers/assets/[name]-[hash][extname]',
            },
        },
    },
});
