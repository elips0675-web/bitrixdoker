#!/usr/bin/env node
/**
 * Гейт документации NLB Bootstrap.
 *
 * Проверяет:
 *  - ссылки в markdown/txt на существующие файлы;
 *  - что каждый документ из docs/ упомянут в docs/README.md;
 *  - что в `Что доделать.txt` нет закрытых пунктов (строки с ✅);
 *  - что `Что сделано.txt` начинается с заголовка;
 *  - что обязательные разделы есть в README.
 *
 * Запуск: node tools/check-docs.mjs
 * Выход: 0 — ошибок нет, 1 — есть ошибки.
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const errors = [];
const checks = [];
const warnings = [];

function read(rel) {
    const abs = path.join(root, rel);
    if (!fs.existsSync(abs)) {
        errors.push(`нет файла: ${rel}`);
        return '';
    }
    return fs.readFileSync(abs, 'utf8');
}

function fail(message) {
    errors.push(message);
}

function ok(message) {
    checks.push(message);
}

/* ------------------------------------------------------------------ */
/* 1. Собираем документы                                              */
/* ------------------------------------------------------------------ */

function walk(dir, acc = []) {
    if (!fs.existsSync(dir)) return acc;
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        if (entry.name === 'node_modules' || entry.name === '.git') continue;
        const abs = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            walk(abs, acc);
        } else if (/\.(md|txt)$/i.test(entry.name)) {
            acc.push(abs);
        }
    }
    return acc;
}

const docs = walk(root)
    .filter((abs) => !abs.includes(`${path.sep}tools${path.sep}`))
    .map((abs) => path.relative(root, abs).replaceAll(path.sep, '/'));

if (!docs.length) {
    errors.push('не найдено ни одного .md/.txt документа');
}

/* ------------------------------------------------------------------ */
/* 2. Ссылки                                                          */
/* ------------------------------------------------------------------ */

const linkRe = /\[[^\]]*\]\(([^)]+)\)/g;

for (const rel of docs) {
    const text = read(rel);
    const dir = path.dirname(path.join(root, rel));

    for (const match of text.matchAll(linkRe)) {
        let target = match[1].trim();

        // отбрасываем title: (path "title")
        target = target.replace(/\s+"[^"]*"$/, '').trim();
        // <path>
        target = target.replace(/^<|>$/g, '');

        if (!target) continue;
        if (/^(https?:|mailto:|tel:|#)/i.test(target)) continue;
        if (target.startsWith('{')) continue;

        const hashIndex = target.indexOf('#');
        if (hashIndex !== -1) target = target.slice(0, hashIndex);
        if (!target) continue;

        const decoded = decodeURIComponent(target);
        const abs = path.resolve(dir, decoded);

        if (!fs.existsSync(abs)) {
            fail(`${rel}: битая ссылка → ${target}`);
        }
    }
}
ok(`ссылки проверены в ${docs.length} файлах`);

/* ------------------------------------------------------------------ */
/* 3. docs/README.md — карта                                          */
/* ------------------------------------------------------------------ */

const map = read('docs/README.md');
const docsDir = path.join(root, 'docs');

if (fs.existsSync(docsDir)) {
    const docFiles = fs.readdirSync(docsDir)
        .filter((name) => name.endsWith('.md') && name !== 'README.md');

    for (const name of docFiles) {
        if (!map.includes(name)) {
            fail(`docs/${name} не упомянут в docs/README.md`);
        }
    }
    ok(`документов в docs/: ${docFiles.length + 1}`);
}

const archiveIndex = path.join(docsDir, 'archive/README.md');
if (fs.existsSync(archiveIndex)) {
    ok('архив: индекс есть');
} else {
    fail('нет docs/archive/README.md');
}

/* ------------------------------------------------------------------ */
/* 4. Бэклог не содержит закрытых пунктов                             */
/* ------------------------------------------------------------------ */

const backlog = read('Что доделать.txt');
if (backlog) {
    const closed = backlog.split('\n').filter((line) => line.includes('✅'));
    if (closed.length) {
        fail(`Что доделать.txt: ${closed.length} строк с ✅ — закрытые пункты должны уйти в Что сделано.txt`);
    }
    if (!backlog.includes('P0')) {
        fail('Что доделать.txt: нет раздела P0');
    }
    ok('бэклог: закрытых пунктов нет');
}

/* ------------------------------------------------------------------ */
/* 5. Журнал                                                          */
/* ------------------------------------------------------------------ */

const done = read('Что сделано.txt');
if (done) {
    if (!done.startsWith('# Что сделано')) {
        fail('Что сделано.txt должен начинаться с «# Что сделано»');
    }
    if (!/Stage \d+/.test(done)) {
        warn('Что сделано.txt: нет записей вида «Stage N»');
    }
    if (!/03\.11\.2026/.test(done)) {
        warn('Что сделано.txt: нет записи от 03.11.2026');
    }
    ok('журнал: структура');
}

/* ------------------------------------------------------------------ */
/* 6. README — обязательные разделы                                   */
/* ------------------------------------------------------------------ */

const readme = read('README.md');
for (const section of ['Быстрый старт', 'Документация', 'Требования']) {
    if (!readme.includes(section)) {
        fail(`README.md: нет раздела «${section}»`);
    }
}
ok('README: обязательные разделы');

/* ------------------------------------------------------------------ */
/* Итог                                                               */
/* ------------------------------------------------------------------ */

if (warnings.length) {
    console.log('Предупреждения:');
    for (const message of warnings) console.log(`  ! ${message}`);
}

if (errors.length) {
    console.error('Ошибки:');
    for (const message of errors) console.error(`  x ${message}`);
    console.error(`\nГейт провален: ${errors.length} ошибок.`);
    process.exit(1);
}

console.log('OK:');
for (const message of checks) console.log(`  + ${message}`);
console.log('\nГейт пройден: 0 ошибок.');
