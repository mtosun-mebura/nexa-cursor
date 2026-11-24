#!/usr/bin/env node
/**
 * Render a composition with props from a JSON file.
 *
 * Usage:
 *   node scripts/render-with-props.mjs --composition NexaProductPromo --props ./props.json --out ./out/promo.mp4
 */
import {spawnSync} from 'node:child_process';
import {existsSync, mkdirSync} from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');

function arg(name, fallback = null) {
  const i = process.argv.indexOf(`--${name}`);
  if (i === -1) return fallback;
  return process.argv[i + 1] ?? fallback;
}

const composition = arg('composition', 'NexaProductPromo');
const propsFile = arg('props', path.join(root, 'props.json'));
const outFile = arg(
  'out',
  path.join(root, 'out', `${composition.toLowerCase()}.mp4`),
);

if (!existsSync(propsFile)) {
  console.error(`Props file not found: ${propsFile}`);
  process.exit(1);
}

mkdirSync(path.dirname(outFile), {recursive: true});

const result = spawnSync(
  'npx',
  [
    'remotion',
    'render',
    'src/index.ts',
    composition,
    outFile,
    `--props=${propsFile}`,
  ],
  {cwd: root, stdio: 'inherit', shell: process.platform === 'win32'},
);

process.exit(result.status ?? 1);
