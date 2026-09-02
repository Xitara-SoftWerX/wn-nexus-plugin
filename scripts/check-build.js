#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';

const expectedFiles = [
    'assets/assets-manifest.json',
    'assets/css/backend.css',
    'assets/css/compact.css',
    'assets/css/exception.css',
    'assets/index.html',
    'assets/js/app.js',
    'assets/js/backend.js',
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
    'assets/js/compact.js',
    'assets/js/exception.js',
]) {
    if (fs.existsSync(emptyScript)) {
        throw new Error(`Unexpected stylesheet loader artifact: ${emptyScript}`);
    }
}

const compactCss = fs.readFileSync('assets/css/compact.css', 'utf8');

if (!compactCss.includes('.sidenav-tree')) {
    throw new Error('Compact backend styles are missing from assets/css/compact.css.');
}

console.log('Build artifacts look complete.');
