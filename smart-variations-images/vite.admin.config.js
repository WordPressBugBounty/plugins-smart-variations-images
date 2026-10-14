import { defineConfig } from 'vite';
import { resolve } from 'path';

/**
 * SVI admin bundle.
 *
 * A second Vite entry (the front-end build in vite.config.js is untouched)
 * that emits the two files `SMART_SCRIPT_DEBUG` chooses between:
 *
 *   admin/js/smart-variations-images-admin.js      (development mode)
 *   admin/js/smart-variations-images-admin.min.js  (production mode)
 *
 * These keep the historical filenames so the enqueue in
 * class-smart-variations-images-admin.php stays untouched; only their content
 * changed (they are now generated instead of hand-written).
 *
 * Run with `npm run build:admin`, or as part of `npm run prod`.
 */
export default defineConfig(({ mode }) => {
  const isProd = mode === 'production';
  const ext = isProd ? '.min' : '';

  const banner = `/*!
 * Smart Variations Images - admin bundle.
 * GENERATED FILE - do not edit. Source of truth: src/admin/.
 * Change the sources and run: npm run build:admin
 */`;

  return {
    base: './',
    publicDir: false,
    build: {
      outDir: resolve(__dirname, 'admin'),
      emptyOutDir: false,
      manifest: false,
      sourcemap: false,
      minify: isProd,
      target: 'es2018',
      cssCodeSplit: false,
      rollupOptions: {
        input: resolve(__dirname, 'src/admin/index.js'),
        output: {
          format: 'iife',
          name: 'SVIAdmin',
          banner,
          entryFileNames: `js/smart-variations-images-admin${ext}.js`,
          assetFileNames: (assetInfo) => {
            if (assetInfo.name && assetInfo.name.endsWith('.css')) {
              return `css/smart-variations-images-admin${ext}.css`;
            }

            return `js/[name]${ext}[extname]`;
          },
        },
      },
    },
  };
});
