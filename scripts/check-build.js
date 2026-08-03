#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';

const expectedFiles = [
    'assets/assets-manifest.json',
    'assets/css/breakpoints.css',
    'assets/css/styles.css',
    'assets/css/tailwind.css',
    'assets/index.html',
    'assets/js/app.js',
];

for (const filename of expectedFiles) {
    const stats = fs.statSync(path.resolve(filename));

    if (!stats.isFile() || stats.size === 0) {
        throw new Error(`Missing or empty build artifact: ${filename}`);
    }
}

for (const emptyScript of [
    'assets/js/styles.js',
    'assets/js/tailwind.js',
    'assets/js/breakpoints.js',
]) {
    if (fs.existsSync(emptyScript)) {
        throw new Error(`Unexpected stylesheet loader artifact: ${emptyScript}`);
    }
}

const bootstrapCss = fs.readFileSync('assets/css/styles.css', 'utf8');
const tailwindCss = fs.readFileSync('assets/css/tailwind.css', 'utf8');

if (!bootstrapCss.includes('--bs-body')) {
    throw new Error('Bootstrap base styles are missing from assets/css/styles.css.');
}

if (!tailwindCss.includes('tw\\:grid')) {
    throw new Error('Prefixed Tailwind utilities are missing from assets/css/tailwind.css.');
}

console.log('Build artifacts look complete.');
