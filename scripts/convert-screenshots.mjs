/**
 * Convert screenshot/*.jpg → docs/screenshots/*.webp (quality 92, near-lossless).
 * Usage: node scripts/convert-screenshots.mjs
 */
import { readdir, mkdir } from 'node:fs/promises';
import { join, basename, extname } from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const root = join(fileURLToPath(new URL('.', import.meta.url)), '..');
const srcDir = join(root, 'screenshot');
const outDir = join(root, 'docs', 'screenshots');

await mkdir(outDir, { recursive: true });
const files = (await readdir(srcDir)).filter((f) => /\.(jpe?g|png)$/i.test(f));
let ok = 0;
for (const file of files) {
  const base = basename(file, extname(file));
  const out = join(outDir, base + '.webp');
  await sharp(join(srcDir, file))
    .webp({ quality: 92, effort: 6, smartSubsample: true })
    .toFile(out);
  ok++;
  console.log('OK', base + '.webp');
}
console.log(`Converted ${ok} images → docs/screenshots/`);