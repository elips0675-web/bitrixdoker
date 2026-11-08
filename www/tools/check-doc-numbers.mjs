#!/usr/bin/env node
/**
 * Гейт чисел в документации NLB Bootstrap.
 *
 * Идея (как в SwiftMatch): источник чисел — код, а не документ. Факты считает
 * `php db/stats.php --json`, гейт сверяет с ними каждое утверждение из списка
 * CLAIMS. Утверждение привязано к файлу и шаблону, поэтому «шесть инфоблоков»
 * в `docs/db.md:336` (там речь о шести контентных) не путается со сводным
 * числом инфоблоков.
 *
 * Если утверждение пропало из документа — это тоже ошибка: значит, гейт
 * перестал его проверять и молча ослаб.
 *
 * Запуск: node tools/check-doc-numbers.mjs
 * Выход:  0 — числа сходятся, 1 — расхождение или гейт не смог проверить.
 */

import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const errors = [];
const checks = [];

/* ------------------------------------------------------------------ */
/* 1. Факты из кода                                                    */
/* ------------------------------------------------------------------ */

function findPhp() {
    const candidates = [];
    if (process.env.NLB_PHP) {
        candidates.push(process.env.NLB_PHP);
    }
    candidates.push('php');
    const laragon = 'C:/laragon/bin/php';
    if (fs.existsSync(laragon)) {
        const versions = fs.readdirSync(laragon).filter((name) => {
            const exe = path.join(laragon, name, 'php.exe');
            return fs.existsSync(exe);
        });
        versions.sort();
        for (const name of versions.reverse()) {
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
    console.error('check-doc-numbers: PHP не найден.');
    console.error('  Числа в документации не с чем сверять, а молчаливый пропуск — это зелёный гейт без единой проверки.');
    console.error('  Укажите путь: $env:NLB_PHP = "C:\\laragon\\bin\\php\\php-8.5.12-Win32-vs17-x64\\php.exe"');
    process.exit(1);
}

let facts;
try {
    facts = JSON.parse(execFileSync(php, [path.join(root, 'db/stats.php'), '--json'], {
        encoding: 'utf8',
        cwd: root,
        maxBuffer: 8 * 1024 * 1024,
        timeout: 180000,
    }));
} catch (error) {
    console.error('check-doc-numbers: db/stats.php не отдал факты.');
    console.error(String(error.stderr || error.message).trim());
    process.exit(1);
}

/* ------------------------------------------------------------------ */
/* 2. Слова-числа                                                      */
/* ------------------------------------------------------------------ */

const WORDS = {
    один: 1, два: 2, три: 3, четыре: 4, пять: 5, шесть: 6, семь: 7,
    восемь: 8, девять: 9, десять: 10, одиннадцать: 11, двенадцать: 12,
};

/* ------------------------------------------------------------------ */
/* 3. Утверждения: файл + шаблон + факт                                */
/* ------------------------------------------------------------------ */

const claims = [
    /* README.md — сводная таблица и итоги проверки */
    { file: 'README.md', re: /^\| Типов инфоблоков \| (\d+)/m, keys: ['types'] },
    { file: 'README.md', re: /^\| Инфоблоков \| (\d+)/m, keys: ['iblocks'] },
    { file: 'README.md', re: /^\| Свойств \| (\d+)/m, keys: ['properties'] },
    { file: 'README.md', re: /^\| Разделов \| (\d+)/m, keys: ['sections'] },
    { file: 'README.md', re: /^\| Элементов демо-контента \| (\d+)/m, keys: ['elements'] },
    { file: 'README.md', re: /автотест на заглушках ядра \((\d+) проверк(?:а|ок)\)/, keys: ['db_tests'] },
    { file: 'README.md', re: /фильтр чата: (\d+) фраз, (\d+) проверок/, keys: ['filter_cases', 'filter_checks'] },
    { file: 'README.md', re: /поиск: индекс, математика, (\d+) запросов/, keys: ['search_cases'] },
    { file: 'README.md', re: /собранный индекс: (\d+) документов, (\d+) термов/, keys: ['index_docs', 'index_terms'] },
    { file: 'README.md', re: /`db\/tests\/run\.php` — (\d+) из (\d+)/, keys: ['db_tests', 'db_tests'] },
    { file: 'README.md', re: /`filter-selftest\.php` — (\d+) из (\d+)/, keys: ['filter_checks', 'filter_checks'] },
    { file: 'README.md', re: /`search-selftest\.php` — (\d+) из (\d+)/, keys: ['search_checks', 'search_checks'] },
    { file: 'README.md', re: /на (\d+) фразах/, keys: ['filter_cases'] },
    { file: 'README.md', re: /\((\d+) файл(?:ов|а)? вне `preview\/`/, keys: ['php_files'] },

    /* project-context.md */
    { file: 'project-context.md', re: /`styles\.css`, ([\d ]+) строк/, keys: ['css_lines'] },
    { file: 'project-context.md', re: /`db\/`, (\d+) инфоблоков, (\d+) элемент/, keys: ['iblocks', 'elements'] },
    { file: 'project-context.md', re: /Синтаксис всех (\d+) PHP-файл(?:ов|а)?/, keys: ['php_files'] },

    /* AGENTS.md */
    { file: 'AGENTS.md', re: /по всем (\d+) PHP-файл(?:ам|у)?/, keys: ['php_files'] },

    /* Как работает.txt */
    { file: 'Как работает.txt', re: /(\d+) инфоблоков, включая bookings/, keys: ['iblocks'] },

    /* Что доделать.txt */
    { file: 'Что доделать.txt', re: /все (\d+) PHP-файл(?:ов|а)? вне/, keys: ['php_files'] },
    { file: 'Что доделать.txt', re: /\(`php -l` — (\d+) файл(?:ов|а)?, 0 ошибок на 8\.3\.30/, keys: ['php_files'] },

    /* db/README.md */
    { file: 'db/README.md', re: /Ожидаемый вывод `run\.php`: (\d+) шагов/, keys: ['migrations'] },
    { file: 'db/README.md', re: /: ([А-Яа-яЁё]+) инфоблоков с/, keys: ['iblocks'], words: true },

    /* docs/ */
    { file: 'docs/AGENTS-frontend.md', re: /`styles\.css` \(наш, ([\d ]+) строк\)/, keys: ['css_lines'] },
    { file: 'docs/installation.md', re: /# (\d+) шагов: типы/, keys: ['migrations'] },
    { file: 'docs/installation.md', re: /Создаётся (\d+) инфоблоков/, keys: ['iblocks'] },
    {
        file: 'docs/installation.md',
        re: /, (\d+) свойств, (\d+) разделов, (\d+) демо-элемент/,
        keys: ['properties', 'sections', 'elements'],
    },
    { file: 'docs/roadmap.md', re: /всех PHP-файлов \((\d+) файл(?:ов|а)?, 0 ошибок\)/, keys: ['php_files'] },
    {
        file: 'docs/roadmap.md',
        re: /слой БД: (\d+) инфоблоков, (\d+) свойств, (\d+) элемент, (\d+) миграций/,
        keys: ['iblocks', 'properties', 'elements', 'migrations'],
    },
    { file: 'docs/security-performance.md', re: /`script\.js`[^\d\n]{0,12}([\d,]+) КБ/, keys: ['js_bytes'], kb: true },
    { file: 'docs/security-performance.md', re: /`styles\.css` ([\d,]+) КБ/, keys: ['css_bytes'], kb: true },

    /* test/ — набор для внешнего аудита */
    { file: 'test/01-ПРОДУКТ.txt', re: /^    PHP-файлов \(без preview\)[ \t]+(\d+)/m, keys: ['php_files'] },
    { file: 'test/01-ПРОДУКТ.txt', re: /^    Строк PHP[ \t]+([\d ]+)/m, keys: ['php_lines'] },
    { file: 'test/01-ПРОДУКТ.txt', re: /^    Миграций[ \t]+(\d+)/m, keys: ['migrations'] },
    { file: 'test/01-ПРОДУКТ.txt', re: /^    Строк styles\.css[ \t]+([\d ]+)/m, keys: ['css_lines'] },
    { file: 'test/01-ПРОДУКТ.txt', re: /^    HTML в статическом превью[ \t]+(\d+)/m, keys: ['preview_html'] },
    { file: 'test/01-ПРОДУКТ.txt', re: /^    Документов docs\/\*\.md[ \t]+(\d+)/m, keys: ['docs_md'] },
    { file: 'test/01-ПРОДУКТ.txt', re: /заявлено (\d+) файл(?:ов|а)?/, keys: ['php_files'] },
    { file: 'test/01-ПРОДУКТ.txt', re: /\((\d+) проверк(?:а|ок) на заглушках/, keys: ['db_tests'] },
    { file: 'test/02-СТРУКТУРА-И-ФАЙЛЫ.txt', re: /^[ \t]*styles\.css[ \t]+([\d ]+) строк/m, keys: ['css_lines'] },
    { file: 'test/02-СТРУКТУРА-И-ФАЙЛЫ.txt', re: /^[ \t]*script\.js[ \t]+([\d ]+) строк/m, keys: ['script_js_lines'] },
    { file: 'test/03-ТЕХНИКА-И-ДАННЫЕ.txt', re: /Свой JS[ \t]+ES5-совместимый, ([\d ]+) строк/, keys: ['script_js_lines'] },
    { file: 'test/03-ТЕХНИКА-И-ДАННЫЕ.txt', re: /([\d ]+) строк[ \t]+styles\.css/, keys: ['css_lines'] },
    { file: 'test/Для анализа.txt', re: /^[ \t]*script\.js[ \t]+([\d ]+) строк/m, keys: ['script_js_lines'] },
    { file: 'test/Для анализа.txt', re: /Свой JS[ \t]+ES5-совместимый, ([\d ]+) строк/, keys: ['script_js_lines'] },
    { file: 'test/04-СОСТОЯНИЕ-И-РИСКИ.txt', re: /\((\d+) файл(?:ов|а)?, PHP/, keys: ['php_files'] },
    { file: 'test/04-СОСТОЯНИЕ-И-РИСКИ.txt', re: /db\/tests\/run\.php (\d+) из (\d+)/, keys: ['db_tests', 'db_tests'] },

    { file: 'test/Для анализа.txt', re: /^    PHP-файлов \(без preview\)[ \t]+(\d+)/m, keys: ['php_files'] },
    { file: 'test/Для анализа.txt', re: /^    Строк PHP[ \t]+([\d ]+)/m, keys: ['php_lines'] },
    { file: 'test/Для анализа.txt', re: /^    Миграций[ \t]+(\d+)/m, keys: ['migrations'] },
    { file: 'test/Для анализа.txt', re: /^    Строк styles\.css[ \t]+([\d ]+)/m, keys: ['css_lines'] },
    { file: 'test/Для анализа.txt', re: /^    HTML в статическом превью[ \t]+(\d+)/m, keys: ['preview_html'] },
    { file: 'test/Для анализа.txt', re: /^    Документов docs\/\*\.md[ \t]+(\d+)/m, keys: ['docs_md'] },
    { file: 'test/Для анализа.txt', re: /синтаксис PHP \((\d+) файл(?:ов|а)?, 0 ошибок/, keys: ['php_files'] },
    { file: 'test/Для анализа.txt', re: /^[ \t]*styles\.css[ \t]+([\d ]+) строк/m, keys: ['css_lines'] },
    { file: 'test/Для анализа.txt', re: /([\d ]+) строк[ \t]+styles\.css/, keys: ['css_lines'] },
    { file: 'test/Для анализа.txt', re: /db\/tests\/run\.php[ \t]+(\d+) проверк(?:а|ок)/, keys: ['db_tests'] },
    { file: 'test/Для анализа.txt', re: /\((\d+) файл(?:ов|а)?, PHP/, keys: ['php_files'] },
    { file: 'test/Для анализа.txt', re: /db\/tests\/run\.php (\d+) из (\d+)/, keys: ['db_tests', 'db_tests'] },
];

const kbOf = (bytes) => Math.round((bytes / 1024) * 10) / 10;

function expected(claim, index) {
    const key = claim.keys[index];
    return claim.kb ? kbOf(facts[key]) : facts[key];
}

let totalChecked = 0;
const perFile = new Map();

for (const claim of claims) {
    const full = path.join(root, claim.file);
    if (!fs.existsSync(full)) {
        errors.push(`${claim.file}: файла нет — утверждение не проверить`);
        continue;
    }
    const text = fs.readFileSync(full, 'utf8');
    const re = new RegExp(claim.re.source, claim.re.flags.includes('g') ? claim.re.flags : claim.re.flags + 'g');
    const matches = [...text.matchAll(re)];
    if (matches.length === 0) {
        errors.push(`${claim.file}: утверждение «${claim.re.source}» не найдено — гейт ослабел или текст переписали без сверки`);
        continue;
    }
    for (const match of matches) {
        const lineNumber = text.slice(0, match.index).split('\n').length;
        for (let group = 1; group < match.length; group++) {
            const key = claim.keys[group - 1];
            if (!key) continue;
            let written;
            if (claim.words) {
                written = WORDS[String(match[group]).toLowerCase()];
            } else {
                written = Number(String(match[group]).replace(/[\s\u00a0]/g, '').replace(',', '.'));
            }
            const want = expected(claim, group - 1);
            totalChecked++;
            perFile.set(claim.file, (perFile.get(claim.file) || 0) + 1);
            if (!Number.isFinite(written)) {
                errors.push(`${claim.file}:${lineNumber}: не разобрано число «${match[group]}» (${key})`);
                continue;
            }
            if (!Number.isFinite(want)) {
                errors.push(`check-doc-numbers: в db/stats.php нет факта «${key}»`);
                continue;
            }
            const equal = claim.kb ? Math.abs(written - want) < 0.06 : written === want;
            if (!equal) {
                const unit = claim.kb ? ' КБ' : '';
                errors.push(`${claim.file}:${lineNumber}: написано ${match[group]}${unit}, факт ${String(want).replace('.', ',')}${unit}  (${key})`);
            }
        }
    }
}

/* ------------------------------------------------------------------ */
/* 4. Устаревшие значения в живых документах                           */
/* ------------------------------------------------------------------ */

const live = (name) => /^(README\.md|AGENTS\.md|project-context\.md|Промты\.txt|Как работает\.txt|Что доделать\.txt|db\/README\.md|docs\/)/.test(name);

const banned = [
    { re: /~\s?1\s?800 строк/, message: () => `устаревшее число строк styles.css, факт ${facts.css_lines}` },
    { re: /3\s?431/, message: () => `устаревшее число строк styles.css, факт ${facts.css_lines}` },
    { re: /8,4\s*КБ/, message: () => `устаревший размер script.js, факт ${kbOf(facts.js_bytes)} КБ` },
    { re: /47,6\s*КБ/, message: () => `устаревший размер styles.css, факт ${kbOf(facts.css_bytes)} КБ` },
    {
        re: /\b(?:6|33|39|48|89|83|64)\s+(?:инфоблок|свойств|элемент|PHP-файл|файл)/,
        files: ['README.md', 'project-context.md', 'docs/installation.md', 'db/README.md', 'Как работает.txt', 'docs/roadmap.md'],
        message: () => `устаревшее сводное число (факт: ${facts.iblocks} инфоблоков, ${facts.properties} свойств, ${facts.elements} элементов, ${facts.php_files} PHP-файлов)`,
    },
    { re: /\b(?:6|8|10|11)\s+(?:миграц|шагов)/, files: ['docs/roadmap.md', 'docs/installation.md', 'db/README.md'], message: () => `устаревшее число шагов миграций, факт ${facts.migrations}` },
];

function liveDocs() {
    const out = [];
    for (const name of fs.readdirSync(root)) {
        if (!/\.(md|txt)$/.test(name)) continue;
        if (name === 'CHANGELOG.md' || name === 'Что сделано.txt') continue;
        if (/^(Оценка|Аудит по документации|Smart Site Search Manual|II bot)/.test(name)) continue;
        out.push(name);
    }
    out.push('db/README.md');
    for (const name of fs.readdirSync(path.join(root, 'docs'))) {
        if (name.endsWith('.md')) out.push('docs/' + name);
    }
    return out.filter(live);
}

let bannedChecked = 0;
for (const name of liveDocs()) {
    const lines = fs.readFileSync(path.join(root, name), 'utf8').split(/\r?\n/);
    lines.forEach((line, index) => {
        for (const rule of banned) {
            if (rule.files && !rule.files.includes(name)) continue;
            if (rule.re.test(line)) {
                errors.push(`${name}:${index + 1}: ${rule.message()}`);
            }
            bannedChecked++;
        }
    });
}

/* ------------------------------------------------------------------ */
/* 5. Гейт не должен молча ослабнуть                                   */
/* ------------------------------------------------------------------ */

if (totalChecked < 40) {
    errors.push(`check-doc-numbers: проверено всего ${totalChecked} утверждений — это меньше, чем было при настройке гейта`);
}
if (bannedChecked === 0) {
    errors.push('check-doc-numbers: список устаревших значений не применён ни к одному документу');
}

/* ------------------------------------------------------------------ */
/* 6. Итог                                                             */
/* ------------------------------------------------------------------ */

if (errors.length > 0) {
    console.error(`Гейт чисел провален: ${errors.length} находок.\n`);
    for (const error of errors) console.error('  - ' + error);
    process.exit(1);
}

checks.push(`утверждений проверено: ${totalChecked} в ${perFile.size} файлах`);
checks.push(`устаревших значений не найдено (${bannedChecked} сравнений в ${liveDocs().length} документах)`);
console.log('OK:');
for (const line of checks) console.log('  + ' + line);
console.log('\nГейт чисел пройден.');
