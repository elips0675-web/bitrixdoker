#!/usr/bin/env node
/**
 * Гейт документации стенда NLB Bootstrap.
 *
 * Повторяет правила шаблона (D:\nlbbootstrap-main\tools\check-docs.mjs и
 * check-doc-numbers.mjs) в масштабе стенда:
 *  - каждая markdown-ссылка в корневых документах ведёт на существующий файл;
 *  - каждый корневой .md/.txt указан в карте README («что где правда»);
 *  - в бэклоге нет закрытых пунктов (✅) и у каждого пункта есть путь или
 *    команда, а не только пересказ;
 *  - журнал начинается с заголовка, записи идут свежим сверху;
 *  - числа в README сверяются с `tools/stand-facts.php --json`, а не с
 *    прошлыми отчётами.
 *
 * Ослабление гейта — ошибка: нет PHP, пропало утверждение, проверено слишком
 * мало утверждений — exit 1, а не «пропущено».
 *
 * Запуск: node tools/check-stand-docs.mjs
 * Выход:  0 — документация сходится, 1 — есть находки.
 */

import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const errors = [];
const checks = [];

function read(rel) {
    const abs = path.join(root, rel);
    if (!fs.existsSync(abs)) {
        errors.push(`нет файла: ${rel}`);
        return '';
    }
    return fs.readFileSync(abs, 'utf8');
}

/* ------------------------------------------------------------------ */
/* 1. Набор документов и карта                                        */
/* ------------------------------------------------------------------ */

const docs = fs.readdirSync(root)
    .filter((name) => /\.(md|txt)$/i.test(name))
    .sort();

if (docs.length < 3) {
    errors.push(`корневых документов ${docs.length} — ожидались README.md, Что доделать.txt, Что сделано.txt`);
}

const readme = read('README.md');

if (!readme.includes('## Документация')) {
    errors.push('README.md: нет раздела «## Документация» — карта документов обязательна');
}

for (const name of docs) {
    if (!readme.includes(name) && !readme.includes(encodeURI(name))) {
        errors.push(`README.md: документ «${name}» не указан в карте — по правилу он считается черновиком`);
    }
}
checks.push(`документов в корне: ${docs.length}, все указаны в карте`);

/* ------------------------------------------------------------------ */
/* 2. Markdown-ссылки                                                 */
/* ------------------------------------------------------------------ */

for (const doc of docs) {
    const text = read(doc);
    for (const match of text.matchAll(/\[[^\]]*\]\(([^)]+)\)/g)) {
        let target = match[1].trim();
        target = target.replace(/\s+"[^"]*"$/, '').trim();
        target = target.replace(/^<|>$/g, '');
        if (!target) continue;
        if (/^(https?:|mailto:|tel:|#)/i.test(target)) continue;
        if (target.startsWith('{')) continue;

        const hash = target.indexOf('#');
        if (hash !== -1) target = target.slice(0, hash);
        if (!target) continue;

        const abs = path.resolve(root, decodeURIComponent(target));
        if (!fs.existsSync(abs)) {
            errors.push(`${doc}: битая ссылка → ${target}`);
        }
    }
}
checks.push('markdown-ссылки: файлы существуют');

/* ------------------------------------------------------------------ */
/* 3. README — обязательные разделы                                   */
/* ------------------------------------------------------------------ */

for (const section of ['## Команды', '## Состояние', '## Документация', '## Восстановить стенд с нуля']) {
    if (!readme.includes(section)) {
        errors.push(`README.md: нет раздела «${section}»`);
    }
}
checks.push('README: обязательные разделы');

/* ------------------------------------------------------------------ */
/* 4. Бэклог                                                          */
/* ------------------------------------------------------------------ */

const backlog = read('Что доделать.txt');

if (backlog) {
    const closed = backlog.split(/\r?\n/).filter((line) => line.includes('✅'));
    if (closed.length) {
        errors.push(`Что доделать.txt: ${closed.length} строк с ✅ — закрытые пункты уходят в Что сделано.txt`);
    }
    if (!/^## P0/m.test(backlog)) {
        errors.push('Что доделать.txt: нет раздела P0');
    }

    const lines = backlog.split(/\r?\n/);
    const items = [...backlog.matchAll(/^### (P\d+-\d+)\.\s+(.+)$/gm)];
    if (items.length === 0) {
        errors.push('Что доделать.txt: нет пунктов вида «### P0-1. …»');
    }

    const hasEvidence = (body) => {
        const tokens = [...body.matchAll(/`([^`]+)`/g)].map((m) => m[1].trim());
        if (tokens.some((token) => /[/\\]|\.(php|js|css|md|txt|sql|yml|yaml|ps1|bat|json)\b|^docker compose|^php |^mysql|^curl/.test(token))) {
            return true;
        }
        if (/docker compose|php \/var\/www|php db\/|mysql -u|curl -/.test(body)) {
            return true;
        }
        return /\b[\w./-]+\.(?:php|js|css|md|txt|sql|yml|ps1):\d+/.test(body);
    };

    for (const item of items) {
        const lineNumber = backlog.slice(0, item.index).split('\n').length;
        let end = lines.length;
        for (let index = lineNumber; index < lines.length; index++) {
            if (/^#{2,3} /.test(lines[index])) {
                end = index;
                break;
            }
        }
        const body = lines.slice(lineNumber, end).join('\n');
        if (!hasEvidence(body)) {
            errors.push(`Что доделать.txt:${lineNumber}: пункт ${item[1]} без пути, команды или file:line — не принимается`);
        }
    }
    checks.push(`бэклог: ${items.length} пунктов, закрытых нет`);
}

/* ------------------------------------------------------------------ */
/* 5. Журнал                                                          */
/* ------------------------------------------------------------------ */

const done = read('Что сделано.txt');

if (done) {
    if (!done.startsWith('# Что сделано')) {
        errors.push('Что сделано.txt должен начинаться с «# Что сделано»');
    }

    const entries = [...done.matchAll(/^\[(\d{2})\.(\d{2})\.(\d{4})\]\s+\S+/gm)];
    if (entries.length === 0) {
        errors.push('Что сделано.txt: нет записей вида «[ДД.ММ.ГГГГ] Тема»');
    }

    let previous = null;
    for (const entry of entries) {
        const date = new Date(Number(entry[3]), Number(entry[2]) - 1, Number(entry[1]));
        if (previous !== null && date > previous) {
            errors.push(`Что сделано.txt: запись ${entry[0].trim()} новее предыдущей — журнал ведётся свежим сверху`);
        }
        previous = date;
    }
    checks.push(`журнал: ${entries.length} записей, свежие сверху`);
}

/* ------------------------------------------------------------------ */
/* 6. Числа: источник — tools/stand-facts.php                         */
/* ------------------------------------------------------------------ */

function findPhp() {
    const candidates = [];
    if (process.env.NLB_PHP) {
        candidates.push(process.env.NLB_PHP);
    }
    candidates.push('php');
    const laragon = 'C:/laragon/bin/php';
    if (fs.existsSync(laragon)) {
        const versions = fs.readdirSync(laragon)
            .filter((name) => fs.existsSync(path.join(laragon, name, 'php.exe')))
            .sort()
            .reverse();
        for (const name of versions) {
            candidates.push(path.join(laragon, name, 'php.exe'));
        }
    }
    for (const candidate of candidates) {
        try {
            execFileSync(candidate, ['-v'], { stdio: 'ignore' });
            return candidate;
        } catch {
            continue;
        }
    }
    return null;
}

const php = findPhp();
if (!php) {
    console.error('check-stand-docs: PHP не найден.');
    console.error('  Числа в README не с чем сверять, а молчаливый пропуск — это зелёный гейт без проверки.');
    console.error('  Укажите путь: $env:NLB_PHP = "C:\\laragon\\bin\\php\\php-8.5.12-Win32-vs17-x64\\php.exe"');
    process.exit(1);
}

let facts;
try {
    facts = JSON.parse(execFileSync(php, [path.join(root, 'tools/stand-facts.php'), '--json'], {
        encoding: 'utf8',
        cwd: root,
        maxBuffer: 8 * 1024 * 1024,
        timeout: 120000,
    }));
} catch (error) {
    console.error('check-stand-docs: tools/stand-facts.php не отдал факты.');
    console.error(String(error.stderr || error.message).trim());
    process.exit(1);
}

const claims = [
    {
        file: 'README.md',
        re: /\((\d+) типа инфоблоков,\s*(\d+) инфоблоков, (\d+) свойств(?:а|о)?, (\d+) разделов, (\d+) элемент/g,
        keys: ['types', 'iblocks', 'properties', 'sections', 'elements'],
    },
    {
        file: 'README.md',
        re: /web \((\d{4})\) \+ cron \+ db MySQL (\d+\.\d+) \((\d{4})\)/g,
        keys: ['port_web', 'mysql_version', 'port_db'],
    },
    {
        file: 'README.md',
        re: /Dockerfile \(php (\d+\.\d+)-apache/g,
        keys: ['php_version'],
    },
    {
        file: 'README.md',
        re: /http:\/\/localhost:(\d+)\//g,
        keys: ['port_web'],
    },
];

let totalChecked = 0;

for (const claim of claims) {
    const text = read(claim.file);
    if (!text) continue;
    const matches = [...text.matchAll(claim.re)];
    if (matches.length === 0) {
        errors.push(`${claim.file}: утверждение «${claim.re.source}» не найдено — гейт ослабел или текст переписали без сверки`);
        continue;
    }
    for (const match of matches) {
        const lineNumber = text.slice(0, match.index).split('\n').length;
        for (let group = 1; group < match.length; group++) {
            const key = claim.keys[group - 1];
            if (!key) continue;
            const written = String(match[group]);
            const want = String(facts[key]);
            totalChecked++;
            if (want === 'undefined') {
                errors.push(`stand-facts.php: нет факта «${key}»`);
                continue;
            }
            if (written !== want) {
                errors.push(`${claim.file}:${lineNumber}: написано ${written}, факт ${want}  (${key})`);
            }
        }
    }
}

if (totalChecked < 9) {
    errors.push(`check-stand-docs: проверено всего ${totalChecked} утверждений — это меньше, чем было при настройке гейта`);
}
checks.push(`чисел сверено с кодом: ${totalChecked}`);

/* ------------------------------------------------------------------ */
/* Итог                                                               */
/* ------------------------------------------------------------------ */

if (errors.length > 0) {
    console.error(`Гейт документации стенда провален: ${errors.length} находок.\n`);
    for (const error of errors) console.error('  - ' + error);
    process.exit(1);
}

console.log('OK:');
for (const message of checks) console.log('  + ' + message);
console.log('\nГейт документации стенда пройден.');
