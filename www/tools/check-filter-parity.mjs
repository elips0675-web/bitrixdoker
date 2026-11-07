#!/usr/bin/env node
/**
 * Гейт паритета фильтра чата: браузер ↔ сервер.
 *
 * Проверяет:
 *  - браузерный блок `script.js` (между маркерами nlb-chat-filter) и серверный
 *    `nlb_text_filter.php` дают одинаковый вердикт на общем наборе фраз
 *    `db/tests/fixtures/filter-cases.json`;
 *  - ожидаемый вердикт из набора совпадает и с браузером, и с сервером;
 *  - списки корней и выражения (blocked / sensitive / spam / mash / study /
 *    neutral / link) совпадают дословно, а не только по поведению на фразах.
 *
 * Почему так: фильтр живёт в двух местах, и правка одной строки в `script.js`
 * без такой же строки в PHP раньше не ловилась ничем — сервер либо пропускал
 * бы то, что блокирует браузер, либо наоборот.
 *
 * Запуск: node tools/check-filter-parity.mjs
 * PHP: ищется в NLB_PHP, затем в PATH, затем в C:/laragon/bin/php/php-*.
 * Выход: 0 — паритет есть, 1 — расхождение.
 */

import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const errors = [];
const checks = [];

const fail = (message) => errors.push(message);
const ok = (message) => checks.push(message);

/* ------------------------------------------------------------------ */
/* 1. Браузерный блок                                                 */
/* ------------------------------------------------------------------ */

const scriptRel = 'local/templates/nlb_bootstrap/script.js';
const scriptPath = path.join(root, scriptRel);
const startMarker = '/* nlb-chat-filter:start */';
const endMarker = '/* nlb-chat-filter:end */';

let client = null;

if (!fs.existsSync(scriptPath)) {
    fail(`нет файла: ${scriptRel}`);
} else {
    const script = fs.readFileSync(scriptPath, 'utf8');
    const from = script.indexOf(startMarker);
    const to = script.indexOf(endMarker);

    if (from === -1 || to === -1 || to <= from) {
        fail(
            `${scriptRel}: нет маркеров ${startMarker} … ${endMarker} — ` +
            'без них гейт не может отделить блок фильтра от остального кода'
        );
    } else {
        const code = script.slice(from + startMarker.length, to);
        try {
            client = vm.runInNewContext(
                code + '\n;({ chatProblem, CHAT_BLOCKED, CHAT_SENSITIVE, CHAT_SPAM, CHAT_MASH, CHAT_STUDY, CHAT_LINK, CHAT_NEUTRAL });',
                Object.create(null)
            );
            for (const name of ['chatProblem', 'CHAT_BLOCKED', 'CHAT_SENSITIVE', 'CHAT_SPAM', 'CHAT_MASH', 'CHAT_STUDY', 'CHAT_LINK', 'CHAT_NEUTRAL']) {
                if (!(name in client)) {
                    fail(`${scriptRel}: в блоке фильтра нет ${name}`);
                }
            }
            if (typeof client.chatProblem !== 'function') {
                fail(`${scriptRel}: chatProblem не функция`);
                client = null;
            }
        } catch (error) {
            fail(`${scriptRel}: блок фильтра не исполняется — ${error.message}`);
        }
    }
}

/* ------------------------------------------------------------------ */
/* 2. Общий набор фраз                                                */
/* ------------------------------------------------------------------ */

const fixtureRel = 'db/tests/fixtures/filter-cases.json';
const fixturePath = path.join(root, fixtureRel);
let cases = null;

if (!fs.existsSync(fixturePath)) {
    fail(`нет файла: ${fixtureRel}`);
} else {
    try {
        const fixture = JSON.parse(fs.readFileSync(fixturePath, 'utf8'));
        if (!Array.isArray(fixture.cases) || fixture.cases.length === 0) {
            fail(`${fixtureRel}: нет массива cases`);
        } else {
            cases = fixture.cases;
            ok(`${fixtureRel}: фраз ${cases.length}`);
        }
    } catch (error) {
        fail(`${fixtureRel}: не JSON — ${error.message}`);
    }
}

/* ------------------------------------------------------------------ */
/* 3. Серверный фильтр через PHP                                      */
/* ------------------------------------------------------------------ */

function findPhp() {
    const candidates = [];
    if (process.env.NLB_PHP) {
        candidates.push(process.env.NLB_PHP);
    }
    candidates.push('php');

    const laragon = 'C:/laragon/bin/php';
    if (fs.existsSync(laragon)) {
        const dirs = fs.readdirSync(laragon)
            .filter((name) => /^php-/i.test(name))
            .sort()
            .reverse();
        for (const dir of dirs) {
            candidates.push(path.join(laragon, dir, 'php.exe'));
        }
    }

    for (const candidate of candidates) {
        const probe = spawnSync(candidate, ['-r', 'echo PHP_VERSION;'], {
            encoding: 'utf8',
            timeout: 20000,
        });
        if (!probe.error && probe.status === 0) {
            return candidate;
        }
    }

    return null;
}

const phpBin = findPhp();
let server = null;

if (!phpBin) {
    fail('PHP не найден: задайте NLB_PHP или добавьте php в PATH');
} else {
    ok(`PHP: ${phpBin}`);
    const run = spawnSync(phpBin, [path.join(root, 'tools/filter-selftest.php'), '--json'], {
        encoding: 'utf8',
        timeout: 60000,
        maxBuffer: 32 * 1024 * 1024,
    });

    if (run.error) {
        fail(`filter-selftest.php --json: не запустился — ${run.error.message}`);
    } else {
        try {
            server = JSON.parse(run.stdout);
        } catch (error) {
            fail(`filter-selftest.php --json: не JSON — ${error.message}`);
            if (run.stderr) fail(`filter-selftest.php: ${run.stderr.trim().split('\n')[0]}`);
        }
    }
}

if (server) {
    if (server.ok !== true) {
        fail(`серверный фильтр не прошёл самопроверку: падений ${server.failures?.length ?? '?'}`);
    }
    if (!Array.isArray(server.cases)) {
        fail('filter-selftest.php --json: нет массива cases');
        server = null;
    }
}

/* ------------------------------------------------------------------ */
/* 4. Списки корней и выражения                                       */
/* ------------------------------------------------------------------ */

const same = (left, right) => JSON.stringify(left) === JSON.stringify(right);

if (client && server) {
    const pairs = [
        ['blocked', 'CHAT_BLOCKED'],
        ['sensitive', 'CHAT_SENSITIVE'],
        ['spam', 'CHAT_SPAM'],
        ['mash', 'CHAT_MASH'],
    ];

    for (const [phpKey, jsKey] of pairs) {
        if (!same(server.lists?.[phpKey], client[jsKey])) {
            const onlyJs = (client[jsKey] || []).filter((item) => !(server.lists?.[phpKey] || []).includes(item));
            const onlyPhp = (server.lists?.[phpKey] || []).filter((item) => !(client[jsKey] || []).includes(item));
            fail(
                `список ${phpKey} расходится: только в script.js — ${JSON.stringify(onlyJs)}, ` +
                `только в nlb_text_filter.php — ${JSON.stringify(onlyPhp)}`
            );
        }
    }

    const regexPairs = [
        ['study', 'CHAT_STUDY'],
        ['neutral', 'CHAT_NEUTRAL'],
        ['link', 'CHAT_LINK'],
    ];

    for (const [phpKey, jsKey] of regexPairs) {
        if (String(server.lists?.[phpKey] ?? '') !== String(client[jsKey].source ?? '')) {
            fail(
                `выражение ${phpKey} расходится: ` +
                `script.js «${client[jsKey].source}», nlb_text_filter.php «${server.lists?.[phpKey]}»`
            );
        }
    }

    ok('списки и выражения совпадают дословно');
}

/* ------------------------------------------------------------------ */
/* 5. Вердикты на общем наборе                                        */
/* ------------------------------------------------------------------ */

if (client && cases) {
    const mismatches = [];
    let checked = 0;

    cases.forEach((testCase, index) => {
        const text = String(testCase.text ?? '');
        const expected = String(testCase.verdict ?? '');
        const actual = client.chatProblem(text);
        checked++;

        if (actual !== expected) {
            mismatches.push(`браузер: «${text}» → «${actual}», ожидалось «${expected}»`);
        }

        if (server && server.cases?.[index]) {
            const serverCase = server.cases[index];
            if (String(serverCase.actual) !== actual) {
                mismatches.push(
                    `браузер «${actual}» ≠ сервер «${serverCase.actual}» на «${text}»`
                );
            }
            if (String(serverCase.expected) !== expected) {
                mismatches.push(`сервер: набор фраз разошёлся на «${text}»`);
            }
        }
    });

    for (const mismatch of mismatches.slice(0, 20)) {
        fail(mismatch);
    }
    if (mismatches.length > 20) {
        fail(`… и ещё ${mismatches.length - 20} расхождений`);
    }

    if (mismatches.length === 0) {
        ok(`вердикты браузера и сервера совпали на ${checked} фразах`);
    }
}

/* ------------------------------------------------------------------ */
/* Итог                                                               */
/* ------------------------------------------------------------------ */

if (errors.length) {
    console.error('Ошибки:');
    for (const message of errors) console.error(`  x ${message}`);
    console.error(`\nГейт провален: ${errors.length} ошибок.`);
    process.exit(1);
}

console.log('OK:');
for (const message of checks) console.log(`  + ${message}`);
console.log('\nГейт пройден: паритет есть, 0 ошибок.');
