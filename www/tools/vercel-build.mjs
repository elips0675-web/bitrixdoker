#!/usr/bin/env node
/**
 * Сборка статического демо для Vercel.
 *
 * Копирует preview/ в public/ — Vercel раздаёт только этот каталог
 * (`outputDirectory` в vercel.json). PHP-файлы, db/, local/ и tools/ в деплой
 * не попадают: Vercel не исполняет PHP и отдавал бы index.php как скачиваемый
 * файл (`Content-Type: application/x-httpd-php`) вместо страницы.
 */

import { cp, mkdir, readdir, rm } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const source = join(root, 'preview');
const target = join(root, 'public');

if (!existsSync(join(source, 'index.html'))) {
    console.error('vercel-build: не найден preview/index.html — скрипт должен лежать в tools/ репозитория');
    process.exit(1);
}

await rm(target, { recursive: true, force: true });
await mkdir(target, { recursive: true });
await cp(source, target, { recursive: true });

const entries = await readdir(target, { recursive: true, withFileTypes: true });
const files = entries.filter((entry) => entry.isFile());
const pages = files.filter((entry) => entry.name.endsWith('.html')).length;

if (pages === 0) {
    console.error('vercel-build: в public/ не оказалось ни одной HTML-страницы');
    process.exit(1);
}

console.log(`vercel-build: public/ готов — HTML-страниц ${pages}, файлов ${files.length}`);
