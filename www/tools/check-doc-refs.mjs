#!/usr/bin/env node
/**
 * Гейт ссылок `file:line` в документации NLB Bootstrap.
 *
 * Ссылка вида `script.js:770` умирает от любой правки файла: строка съезжает,
 * и читатель попадает в пустоту или в закрывающую скобку. Гейт проверяет, что
 * файл существует, строка в диапазоне, не пустая и не состоит из `}`/`});`.
 *
 * Если рядом со ссылкой в тексте стоит якорь (селектор `.breadcrumb-navigation`
 * или имя функции `nlb_forms_after_add`), гейт требует, чтобы якорь был в файле
 * в пределах ±40 строк от указанной. Это ловит «уехавшие» ссылки, не ловя
 * честный сдвиг на одну-две строки.
 *
 * Продолжения вида `db/config/iblocks.php:453, :507, :592` проверяются в том же
 * файле, что и основная ссылка: иначе хвост списка не проверялся вовсе и мог
 * указывать в пустоту. Ссылка без имени файла (`(:877-922)`) не проверяется —
 * поэтому в документах такие места пишутся полным путём.
 *
 * Запуск: node tools/check-doc-refs.mjs
 * Выход:  0 — все ссылки живые, 1 — есть мёртвые или гейт не смог проверить.
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const errors = [];
const warnings = [];

/* ------------------------------------------------------------------ */
/* 1. Какие документы сканируются                                      */
/* ------------------------------------------------------------------ */

const snapshot = /^(CHANGELOG\.md|Что сделано\.txt|Оценка|Аудит по документации|Smart Site Search Manual|II bot)/;

const docs = [];
for (const name of fs.readdirSync(root)) {
    if (snapshot.test(name)) continue;
    if (/\.(md|txt)$/.test(name)) docs.push(name);
}
if (fs.existsSync(path.join(root, 'db/README.md'))) docs.push('db/README.md');
for (const name of fs.readdirSync(path.join(root, 'docs'))) {
    if (name.endsWith('.md')) docs.push('docs/' + name);
}
for (const name of fs.readdirSync(path.join(root, 'test'))) {
    if (name.endsWith('.txt')) docs.push('test/' + name);
}

/* ------------------------------------------------------------------ */
/* 2. Индекс файлов проекта                                            */
/* ------------------------------------------------------------------ */

const skipDirs = new Set(['node_modules', '.git', '.idea', '.vscode', 'test-results']);
const allFiles = [];
const byBasename = new Map();

(function walk(relative) {
    const absolute = relative ? path.join(root, relative) : root;
    for (const entry of fs.readdirSync(absolute, { withFileTypes: true })) {
        if (skipDirs.has(entry.name)) continue;
        const next = relative ? relative + '/' + entry.name : entry.name;
        if (entry.isDirectory()) {
            walk(next);
            continue;
        }
        allFiles.push(next);
        const base = entry.name;
        if (!byBasename.has(base)) byBasename.set(base, []);
        byBasename.get(base).push(next);
    }
})('');

const prefixes = [
    '',
    'local/templates/nlb_bootstrap/',
    'local/templates/nlb_bootstrap/components/',
    'local/templates/nlb_bootstrap/components/bitrix/',
    'local/php_interface/include/',
    'local/php_interface/',
    'local/ajax/',
    'db/',
    'db/config/',
    'db/migrations/',
    'db/menu/',
    'db/tests/',
    'tools/',
    'preview/',
    'assets/',
    'assets/js/',
    'assets/css/',
    'assets/images/',
];

function resolveFile(reference) {
    const clean = reference.replace(/\\/g, '/').replace(/^\.\//, '');
    for (const prefix of prefixes) {
        const candidate = prefix + clean;
        if (fs.existsSync(path.join(root, candidate))) return candidate;
    }
    const base = clean.split('/').pop();
    const matches = byBasename.get(base) || [];
    if (matches.length === 1) return matches[0];
    if (matches.length > 1) {
        const template = matches.find((name) => name.startsWith('local/templates/nlb_bootstrap/'));
        if (template) return template;
    }
    return null;
}

/* ------------------------------------------------------------------ */
/* 3. Ссылки                                                           */
/* ------------------------------------------------------------------ */

const REF = /([\w./\\-]+\.(?:php|css|js|md|txt|json|html|sql|xml|ps1|bat|webmanifest)):(\d+)(?:\s*[-–]\s*(\d+))?/g;
const CHAIN = /^\s*,\s*:(\d+)(?:\s*[-–]\s*(\d+))?/;

const anchorsOnLine = (line) => {
    const tokens = [...line.matchAll(/`([^`]+)`/g)].map((m) => m[1].trim());
    return tokens.filter((token) => {
        if (/[\s:]/.test(token)) return false;
        if (/^[.#][a-z][\w-]*$/i.test(token)) return true;
        if (/^[a-z_][\w]*\(\s*\)$/i.test(token)) return true;
        return /^[a-z][a-z0-9]*_[a-z0-9_]+$/i.test(token);
    });
};

let refsChecked = 0;
const refsByFile = new Map();

function checkRef({ doc, lineNumber, resolved, rawPath, start, end, whole, anchors }) {
    const content = fs.readFileSync(path.join(root, resolved), 'utf8');
    const target = content.split(/\r?\n/);
    if (target.length > 0 && target[target.length - 1] === '') target.pop();

    if (start < 1 || start > target.length) {
        errors.push(`${doc}:${lineNumber}: «${rawPath}:${start}» — в файле ${target.length} строк`);
        return;
    }
    if (end < 1 || end > target.length) {
        errors.push(`${doc}:${lineNumber}: «${rawPath}:${start}-${end}» — в файле ${target.length} строк`);
        return;
    }

    const first = (target[start - 1] || '').trim();
    if (first === '') {
        errors.push(`${doc}:${lineNumber}: «${rawPath}:${start}» ведёт в пустую строку`);
        return;
    }
    if (/^[})\];]+,?;?$/.test(first)) {
        errors.push(`${doc}:${lineNumber}: «${rawPath}:${start}» ведёт на закрывающую скобку: ${first}`);
        return;
    }

    for (const anchor of anchors) {
        const needle = anchor.replace(/\(\s*\)$/, '').replace(/^#/, '');
        const from = Math.max(0, start - 1 - 40);
        const to = Math.min(target.length, start + 40);
        const window = target.slice(from, to).join('\n');
        if (window.includes(needle)) continue;
        const actual = target.findIndex((line) => line.includes(needle));
        errors.push(
            `${doc}:${lineNumber}: якорь «${anchor}» не найден рядом с «${rawPath}:${start}»` +
            (actual >= 0 ? ` — в файле он на строке ${actual + 1}` : ' — в файле его нет вовсе'),
        );
    }
}

for (const doc of docs) {
    const text = fs.readFileSync(path.join(root, doc), 'utf8');
    const lines = text.split(/\r?\n/);

    for (let index = 0; index < lines.length; index++) {
        const docLine = lines[index];
        const lineNumber = index + 1;

        const primary = [];
        const re = new RegExp(REF.source, 'g');
        let match;
        while ((match = re.exec(docLine)) !== null) primary.push(match);
        if (primary.length === 0) continue;

        const anchors = anchorsOnLine(docLine);

        for (const item of primary) {
            const [whole, rawPath, rawStart, rawEnd] = item;
            refsChecked++;
            refsByFile.set(doc, (refsByFile.get(doc) || 0) + 1);

            const resolved = resolveFile(rawPath);
            if (!resolved) {
                errors.push(`${doc}:${lineNumber}: файл «${rawPath}» не найден (${whole})`);
                continue;
            }

            checkRef({
                doc,
                lineNumber,
                resolved,
                rawPath,
                start: Number(rawStart),
                end: rawEnd ? Number(rawEnd) : Number(rawStart),
                whole,
                anchors,
            });

            let consumed = item.index + item[0].length;
            let chain;
            while ((chain = CHAIN.exec(docLine.slice(consumed))) !== null) {
                const chainStart = Number(chain[1]);
                const chainEnd = chain[2] ? Number(chain[2]) : chainStart;
                refsChecked++;
                refsByFile.set(doc, (refsByFile.get(doc) || 0) + 1);
                checkRef({
                    doc,
                    lineNumber,
                    resolved,
                    rawPath,
                    start: chainStart,
                    end: chainEnd,
                    whole: docLine.slice(item.index, consumed).trim() + chain[0],
                    anchors: [],
                });
                consumed += chain[0].length;
            }
        }
    }
}

/* ------------------------------------------------------------------ */
/* 4. Гейт не должен молча ослабнуть                                   */
/* ------------------------------------------------------------------ */

if (refsChecked < 100) {
    errors.push(`check-doc-refs: найдено ${refsChecked} ссылок — это меньше, чем было при настройке гейта (160)`);
}
if (docs.length < 20) {
    errors.push(`check-doc-refs: просканировано ${docs.length} документов — гейт ослабел`);
}

/* ------------------------------------------------------------------ */
/* 5. Итог                                                             */
/* ------------------------------------------------------------------ */

if (errors.length > 0) {
    console.error(`Гейт ссылок провален: ${errors.length} находок.\n`);
    for (const error of errors) console.error('  - ' + error);
    process.exit(1);
}

console.log('OK:');
console.log(`  + ссылок проверено: ${refsChecked} в ${refsByFile.size} документах`);
console.log(`  + документов просканировано: ${docs.length}`);
for (const warning of warnings) console.log('  ~ ' + warning);
console.log('\nГейт ссылок пройден.');
