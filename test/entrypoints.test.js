import assert from 'node:assert/strict';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import {
    createEntryFilename,
    isVersionedEntryAsset,
    resolveEntryPoints,
} from '../webpack/entrypoints.js';

const fixtureRoot = path.join(path.dirname(fileURLToPath(import.meta.url)), 'fixtures');

test('keeps regular string entries unchanged', () => {
    const result = resolveEntryPoints(
        { app: './ts/app.ts' },
        { projectRoot: fixtureRoot, sourceDir: 'src' }
    );

    assert.deepEqual(result, {
        entries: { app: './ts/app.ts' },
        entryVersions: {},
    });
});

test('reads a version when versioned is explicitly enabled', () => {
    const result = resolveEntryPoints(
        {
            app: {
                entry: './ts/app.ts',
                versionFile: 'version.ts',
                versioned: true,
            },
        },
        { projectRoot: fixtureRoot, sourceDir: 'src' }
    );

    assert.equal(result.entries.app, './ts/app.ts');
    assert.equal(result.entryVersions.app, '2.4.6-beta.1');
});

test('keeps legacy entry and versionFile objects versioned by default', () => {
    const result = resolveEntryPoints(
        {
            app: {
                entry: './ts/app.ts',
                versionFile: '../../package.json',
            },
        },
        { projectRoot: fixtureRoot, sourceDir: 'src' }
    );

    assert.equal(result.entryVersions.app, '3.1.4');
});

test('allows versioning to be disabled explicitly', () => {
    const result = resolveEntryPoints(
        {
            app: {
                entry: './ts/app.ts',
                versionFile: 'missing.ts',
                versioned: false,
            },
        },
        { projectRoot: fixtureRoot, sourceDir: 'src' }
    );

    assert.deepEqual(result.entryVersions, {});
});

test('adds versions and development suffixes to generated filenames', () => {
    const filename = createEntryFilename({
        directory: 'js',
        extension: 'js',
        entryVersions: { app: '2.4.6-beta.1' },
        suffix: '.debug',
    });

    assert.equal(filename({ chunk: { name: 'app' } }), 'js/app-2.4.6-beta.1.debug.js');
    assert.equal(filename({ chunk: { name: 'other' } }), 'js/other.debug.js');
});

test('keeps previous files belonging to versioned entries', () => {
    const versions = { app: '2.0.0', theme: '3.0.0' };

    assert.equal(isVersionedEntryAsset('js/app-1.0.0.js', versions), true);
    assert.equal(isVersionedEntryAsset('js/app-1.0.0.debug.js.map', versions), true);
    assert.equal(isVersionedEntryAsset('css/theme-2.0.0.css', versions), true);
    assert.equal(isVersionedEntryAsset('js/other-1.0.0.js', versions), false);
    assert.equal(isVersionedEntryAsset('js/app.js', versions), false);
});
