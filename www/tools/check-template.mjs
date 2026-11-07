#!/usr/bin/env node
/**
 * Структурный гейт шаблона NLB Bootstrap.
 *
 * Проверяет то, что можно проверить без PHP и Битрикса:
 *  - наличие обязательных файлов;
 *  - ссылки на изображения и вендорные ассеты;
 *  - отсутствие путей `../local/` в demo/index.php;
 *  - баланс открывающих/закрывающих тегов PHP;
 *  - баланс фигурных скобок в styles.css;
 *  - целостность SVG-спрайта и ссылок `<use href="#i-...">`;
 *  - наличие id для всех `data-bs-target="#..."`;
 *  - количество карточек в демо и превью.
 *
 * Запуск: node tools/check-template.mjs
 * Выход: 0 — ошибок нет, 1 — есть ошибки.
 */

import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const tplRel = 'local/templates/nlb_bootstrap';
const tpl = path.join(root, tplRel);

const errors = [];
const warnings = [];
const checks = [];

function fail(message) {
    errors.push(message);
}

function warn(message) {
    warnings.push(message);
}

function ok(message) {
    checks.push(message);
}

function read(rel) {
    const abs = path.join(root, rel);
    if (!fs.existsSync(abs)) {
        fail(`нет файла: ${rel}`);
        return null;
    }
    return fs.readFileSync(abs, 'utf8');
}

/* ------------------------------------------------------------------ */
/* 1. Обязательные файлы                                              */
/* ------------------------------------------------------------------ */

const required = [
    'README.md',
    'AGENTS.md',
    'project-context.md',
    'CHANGELOG.md',
    'Что сделано.txt',
    'Что доделать.txt',
    'Промты.txt',
    `${tplRel}/header.php`,
    `${tplRel}/footer.php`,
    `${tplRel}/styles.css`,
    `${tplRel}/script.js`,
    `${tplRel}/.description.php`,
    `${tplRel}/assets/css/bootstrap.min.css`,
    `${tplRel}/assets/js/bootstrap.bundle.min.js`,
    `${tplRel}/components/bitrix/menu/top_bootstrap/template.php`,
    `${tplRel}/components/bitrix/breadcrumb/bootstrap/template.php`,
    `${tplRel}/components/bitrix/news.list/nlb_cards/template.php`,
    `${tplRel}/components/bitrix/news.detail/nlb_detail/template.php`,
    `${tplRel}/components/bitrix/main.feedback/bootstrap/template.php`,
    'preview/index.html',
    'demo/index.php',
    'manifest.webmanifest',
    'sw.js',
    'offline.html',
    `${tplRel}/assets/js/pwa.js`,
    `${tplRel}/assets/js/consent.js`,
    `${tplRel}/assets/icons/icon-192.png`,
    `${tplRel}/assets/icons/icon-512.png`,
    `${tplRel}/assets/icons/icon-maskable-512.png`,
    `${tplRel}/assets/icons/apple-touch-icon.png`,
    `${tplRel}/assets/icons/favicon-32.png`,
    `${tplRel}/assets/icons/icon.svg`,
    'robots.txt',
    'sitemap.php',
    'urlrewrite.php',
    '404.php',
    'local/php_interface/init.php',
    'local/php_interface/include/nlb_db.php',
    'local/php_interface/include/nlb_seo.php',
    'local/php_interface/include/nlb_security.php',
    'local/php_interface/include/nlb_forms.php',
    'local/php_interface/include/nlb_push.php',
    'local/php_interface/include/nlb_text_filter.php',
    'local/ajax/push.php',
    'local/ajax/chat.php',
    'tools/gen-vapid.php',
    'tools/push-selftest.php',
    'tools/filter-selftest.php',
    'tools/check-filter-parity.mjs',
    'db/tests/fixtures/filter-cases.json',
    'local/php_interface/include/nlb_search.php',
    'local/ajax/search.php',
    'tools/build-search-index.php',
    'tools/search-selftest.php',
    'db/search/pages.json',
    'db/search/index.json',
    'db/tests/fixtures/search-cases.json',
    `${tplRel}/assets/js/push.js`,
    'db/config/iblocks.php',
    'db/lib/boot.php',
    'db/lint.php',
    'db/check.php',
    'db/run.php',
    'db/rollback.php',
    'db/tests/run.php',
    'db/menu/top.menu.php',
    'db/menu/bottom.menu.php',
    'db/.htaccess',
    'tools/.htaccess',
    'docs/README.md',
    'docs/architecture.md',
    'docs/installation.md',
    'docs/bitrix-integration.md',
    'docs/bootstrap.md',
    'docs/design-system.md',
    'docs/content.md',
    'docs/customization.md',
    'docs/db.md',
    'docs/qa-checklist.md',
    'docs/security-performance.md',
    'docs/roadmap.md',
    'docs/AGENTS-bitrix.md',
    'docs/AGENTS-frontend.md',
    'docs/archive/README.md',
];

/* Публичные страницы: должны подключать header/footer и задавать заголовок. */
const publicPages = [
    'index.php',
    'about/index.php',
    'news/index.php',
    'news/detail.php',
    'events/index.php',
    'events/detail.php',
    'collections/index.php',
    'collections/detail.php',
    'services/index.php',
    'services/detail.php',
    'printing/index.php',
    'printing/detail.php',
    'books/index.php',
    'books/detail.php',
    'contacts/index.php',
    'feedback/index.php',
    'subscribe/index.php',
    'search/index.php',
    'personal/index.php',
    'auth/index.php',
    'auth/register/index.php',
    'auth/forgot/index.php',
    'auth/change/index.php',
    'rules/index.php',
    'privacy/index.php',
    'terms/index.php',
    'sitemap/index.php',
    '404.php',
];

for (const rel of publicPages) {
    if (!fs.existsSync(path.join(root, rel))) {
        fail(`нет страницы: ${rel}`);
    }
}
if (!errors.length) {
    ok(`публичных страниц: ${publicPages.length}`);
}

for (const rel of required) {
    if (!fs.existsSync(path.join(root, rel))) {
        fail(`нет обязательного файла: ${rel}`);
    }
}
if (!errors.length) {
    ok(`обязательные файлы: ${required.length}`);
}

/* ------------------------------------------------------------------ */
/* 2. Ассеты                                                          */
/* ------------------------------------------------------------------ */

const sources = [
    `${tplRel}/header.php`,
    `${tplRel}/footer.php`,
    'demo/index.php',
    'preview/index.html',
    'db/config/data/news.php',
    'db/config/data/events.php',
    'db/config/data/services.php',
    'db/config/data/printing.php',
    'db/config/data/collections.php',
    'db/config/data/books.php',
];

const referencedImages = new Set();
for (const rel of sources) {
    const text = read(rel);
    if (!text) continue;

    for (const match of text.matchAll(/assets\/images\/([\w.\-]+)/g)) {
        referencedImages.add(match[1]);
    }
}

const imagesDir = path.join(tpl, 'assets/images');
const actualImages = fs.existsSync(imagesDir)
    ? fs.readdirSync(imagesDir).filter((name) => /\.(jpe?g|png|webp|avif|svg)$/i.test(name))
    : [];
const actualSet = new Set(actualImages);

// Варианты не считаются отдельными картинками: photo.webp и photo-480.jpg
// живут вместе с исходником photo.jpg и проверяются вместе с ним.
const isWidthVariant = (name) => /-(480|960|1280)\.(?:jpe?g|webp|avif)$/i.test(name);
const originName = (name) => name
    .replace(/-(480|960|1280)\.(?:jpe?g|webp|avif)$/i, '')
    .replace(/\.(?:webp|avif)$/i, '') + '.jpg';
const isDerived = (name) => isWidthVariant(name)
    || (/\.(?:webp|avif)$/i.test(name) && actualSet.has(originName(name)));

// og-cover.jpg не выводится в разметке: он подставляется в og:image из nlb_seo.php.
const seoHelper = read('local/php_interface/include/nlb_seo.php');
if (seoHelper && seoHelper.includes('og-cover.jpg') && seoHelper.includes('/assets/images/')) {
    referencedImages.add('og-cover.jpg');
}

for (const name of referencedImages) {
    if (!actualSet.has(name)) {
        fail(`ссылка на несуществующее изображение: assets/images/${name}`);
    }
}

let originalCount = 0;
for (const name of referencedImages) {
    if (!/\.jpe?g$/i.test(name) || isDerived(name)) continue;
    originalCount += 1;
    const stem = name.replace(/\.jpe?g$/i, '');
    for (const ext of ['webp', 'avif']) {
        if (!actualSet.has(`${stem}.${ext}`)) {
            fail(`нет варианта assets/images/${stem}.${ext} для ${name}; запустите php tools/build-image-variants.php`);
        }
    }
}

for (const name of actualImages) {
    if (isDerived(name)) continue;
    if (!referencedImages.has(name)) {
        warn(`изображение не используется в разметке: assets/images/${name}`);
    }
}
ok(`изображений: ${actualImages.length}, ссылок: ${referencedImages.size}, исходников с WebP/AVIF: ${originalCount}`);

for (const rel of [`${tplRel}/assets/css/bootstrap.min.css`, `${tplRel}/assets/js/bootstrap.bundle.min.js`]) {
    const abs = path.join(root, rel);
    if (fs.existsSync(abs) && fs.statSync(abs).size < 10_000) {
        fail(`подозрительно маленький вендорный файл: ${rel}`);
    }
}

/* ------------------------------------------------------------------ */
/* 2.1. Адаптивные изображения                                        */
/* ------------------------------------------------------------------ */

const mediaHelper = read('local/php_interface/include/nlb_media.php');
if (mediaHelper) {
    for (const part of ['function nlb_picture', 'image/avif', 'image/webp', 'srcset', 'fetchpriority']) {
        if (!mediaHelper.includes(part)) {
            fail(`nlb_media.php: нет фрагмента ${part}`);
        }
    }
    const initPhp = read('local/php_interface/init.php') || '';
    if (!initPhp.includes('include/nlb_media.php')) {
        fail('init.php: не подключает nlb_media.php');
    }
    ok('nlb_media.php: <picture> с AVIF, WebP и srcset');
}

const variantTool = read('tools/build-image-variants.php');
if (variantTool && (!variantTool.includes('imagewebp') || !variantTool.includes('imageavif'))) {
    fail('tools/build-image-variants.php: нет вызовов imagewebp/imageavif');
}

const componentWithPictures = [
    `${tplRel}/components/bitrix/news.list/nlb_cards/template.php`,
    `${tplRel}/components/bitrix/news.detail/nlb_detail/template.php`,
    `${tplRel}/components/bitrix/news.detail/nlb_service/template.php`,
];
for (const rel of componentWithPictures) {
    const text = read(rel) || '';
    if (!text.includes('nlb_picture(')) {
        fail(`${rel}: картинки выводятся без nlb_picture()`);
    }
    if (/<img\b/.test(text)) {
        fail(`${rel}: остался прямой <img> вместо nlb_picture()`);
    }
}

const demoIndex = read('demo/index.php') || '';
const demoPictures = (demoIndex.match(/nlb_picture\(/g) || []).length;
if (demoPictures < 18) {
    fail(`demo/index.php: nlb_picture ${demoPictures} раз, нужно 18`);
}
if (/<img\b/.test(demoIndex)) {
    fail('demo/index.php: остался прямой <img> вместо nlb_picture()');
}

const homeHtml = read('preview/index.html') || '';
const homePictures = (homeHtml.match(/<picture class="nlb-picture">/g) || []).length;
if (homePictures < 18) {
    fail(`preview/index.html: <picture> ${homePictures}, нужно 18 — пересоберите php tools/build-preview-pages.php`);
}
const homeWithoutPictures = homeHtml.replace(/<picture class="nlb-picture">[\s\S]*?<\/picture>/g, '');
const bareHomeImages = (homeWithoutPictures.match(/<img\b[^>]*src="assets\/images\/[^"]+\.jpe?g"/g) || []).length;
if (bareHomeImages > 0) {
    fail(`preview/index.html: ${bareHomeImages} JPG-картинок без <picture>`);
}

const templateStyles = read(`${tplRel}/styles.css`) || '';
if (!templateStyles.includes('.nlb-picture')) {
    fail('styles.css: нет правила .nlb-picture');
}
ok(`адаптивные картинки: demo ${demoPictures}, главная ${homePictures}, компоненты ${componentWithPictures.length}`);

/* ------------------------------------------------------------------ */
/* 3. demo/index.php                                                  */
/* ------------------------------------------------------------------ */

const demo = read('demo/index.php');
if (demo) {
    if (demo.includes('../local/')) {
        fail('demo/index.php содержит путь ../local/ — должен использовать SITE_TEMPLATE_PATH');
    }
    if (!demo.includes("bitrix/header.php")) {
        fail('demo/index.php не подключает bitrix/header.php');
    }
    if (!demo.includes("bitrix/footer.php")) {
        fail('demo/index.php не подключает bitrix/footer.php');
    }
    if (!demo.includes('SITE_TEMPLATE_PATH')) {
        fail('demo/index.php не использует SITE_TEMPLATE_PATH');
    }
}

/* ------------------------------------------------------------------ */
/* 4. Баланс PHP-тегов                                                */
/* ------------------------------------------------------------------ */

const phpFiles = [
    `${tplRel}/header.php`,
    `${tplRel}/footer.php`,
    `${tplRel}/.description.php`,
    `${tplRel}/components/bitrix/menu/top_bootstrap/template.php`,
    `${tplRel}/components/bitrix/breadcrumb/bootstrap/template.php`,
    'demo/index.php',
];

for (const rel of phpFiles) {
    const text = read(rel);
    if (!text) continue;

    const opens = (text.match(/<\?php/g) || []).length + (text.match(/<\?=/g) || []).length;
    const closes = (text.match(/\?>/g) || []).length;

    // Закрывающий ?> в конце PHP-файла необязателен: допускаем «один открытый
    // тег и ни одного закрывающего» (как в .description.php).
    const closingOptional = opens === 1 && closes === 0;

    if (opens !== closes && !closingOptional) {
        fail(`несбалансированные PHP-теги в ${rel}: открыто ${opens}, закрыто ${closes}`);
    }
    if (text.charCodeAt(0) === 0xfeff) {
        fail(`BOM в начале файла: ${rel}`);
    }
}
ok(`PHP-файлов проверено: ${phpFiles.length}`);

/* ------------------------------------------------------------------ */
/* 5. CSS                                                             */
/* ------------------------------------------------------------------ */

const css = read(`${tplRel}/styles.css`);
if (css) {
    const open = (css.match(/\{/g) || []).length;
    const close = (css.match(/\}/g) || []).length;
    if (open !== close) {
        fail(`несбалансированные фигурные скобки в styles.css: { ${open}, } ${close}`);
    }
    if (!css.includes('--nlb-primary')) {
        fail('styles.css не содержит токен --nlb-primary');
    }
    if (!/@media \(prefers-reduced-motion: reduce\)/.test(css)) {
        warn('styles.css: нет @media (prefers-reduced-motion: reduce)');
    }
    ok('styles.css: скобки и токены');
}

/* ------------------------------------------------------------------ */
/* 6. SVG-спрайт                                                     */
/* ------------------------------------------------------------------ */

const spriteDocs = [
    { rel: `${tplRel}/header.php`, label: 'header.php' },
    { rel: 'preview/index.html', label: 'preview/index.html' },
];

for (const { rel, label } of spriteDocs) {
    const text = read(rel);
    if (!text) continue;

    const symbols = new Set([...text.matchAll(/<symbol\s+id="(i-[\w-]+)"/g)].map((m) => m[1]));
    const uses = new Set([...text.matchAll(/<use\s+href="#(i-[\w-]+)"/g)].map((m) => m[1]));

    if (symbols.size < 30) {
        fail(`${label}: в спрайте только ${symbols.size} символов (ожидается ≥ 30)`);
    }
    for (const id of uses) {
        if (!symbols.has(id)) {
            fail(`${label}: <use href="#${id}"> без символа в спрайте`);
        }
    }
    ok(`${label}: символов ${symbols.size}, использований ${uses.size}`);
}

/* ------------------------------------------------------------------ */
/* 7. data-bs-target → id                                            */
/* ------------------------------------------------------------------ */

const targetDocs = [
    { rel: `${tplRel}/header.php`, label: 'header.php' },
    { rel: `${tplRel}/footer.php`, label: 'footer.php' },
    { rel: 'preview/index.html', label: 'preview/index.html' },
];

for (const { rel, label } of targetDocs) {
    const text = read(rel);
    if (!text) continue;

    const ids = new Set([...text.matchAll(/\sid="([^"]+)"/g)].map((m) => m[1]));
    const targets = new Set([...text.matchAll(/data-bs-target="#([^"]+)"/g)].map((m) => m[1]));

    for (const target of targets) {
        if (!ids.has(target)) {
            fail(`${label}: data-bs-target="#${target}" — нет элемента с таким id`);
        }
    }
    ok(`${label}: data-bs-target ${targets.size}`);
}

/* ------------------------------------------------------------------ */
/* 8. Количество карточек                                             */
/* ------------------------------------------------------------------ */

for (const rel of ['demo/index.php', 'preview/index.html']) {
    const text = read(rel);
    if (!text) continue;

    const counts = {
        'print-card': (text.match(/class="print-card"/g) || []).length,
        'nlb-card': (text.match(/class="nlb-card"/g) || []).length,
        'service-card': (text.match(/class="service-card"/g) || []).length,
        'collection-card': (text.match(/class="collection-card/g) || []).length,
    };

    if (counts['print-card'] !== 8) {
        fail(`${rel}: print-card ${counts['print-card']} вместо 8`);
    }
    if (counts['nlb-card'] !== 7) {
        fail(`${rel}: nlb-card ${counts['nlb-card']} вместо 7 (3 события + 4 новости)`);
    }
    if (counts['service-card'] !== 6) {
        fail(`${rel}: service-card ${counts['service-card']} вместо 6`);
    }
    if (counts['collection-card'] !== 6) {
        fail(`${rel}: collection-card ${counts['collection-card']} вместо 6`);
    }
    ok(`${rel}: карточки 8/7/6/6`);
}

/* ------------------------------------------------------------------ */
/* 9. Публичные страницы                                              */
/* ------------------------------------------------------------------ */

for (const rel of publicPages) {
    const text = read(rel);
    if (!text) continue;

    if (!text.includes('bitrix/header.php')) {
        fail(`${rel}: не подключает bitrix/header.php`);
    }
    if (!text.includes('bitrix/footer.php')) {
        fail(`${rel}: не подключает bitrix/footer.php`);
    }
    // Главная и 404 тоже задают заголовок, иначе <title> пустой.
    if (!text.includes('SetTitle(')) {
        warn(`${rel}: нет $APPLICATION->SetTitle()`);
    }
    if (text.includes("'/map/'") || text.includes('"/map/"')) {
        fail(`${rel}: ссылка на /map/ — страница исключена из объёма`);
    }
}
ok('страницы: header/footer/SetTitle');

/* Один <h1> на страницу. У карусели главной заголовок остаётся только
   у первого слайда, остальные — <p class="hero-title">; собранные
   страницы preview/ должны иметь ровно один заголовок. */
const h1Pages = [...publicPages, 'offline.html', 'demo/index.php'];
(function walk(dir) {
    for (const entry of fs.readdirSync(path.join(root, dir), { withFileTypes: true })) {
        const rel = `${dir}/${entry.name}`;
        if (entry.isDirectory()) {
            walk(rel);
        } else if (entry.name.endsWith('.html')) {
            h1Pages.push(rel);
        }
    }
})('preview');

let h1Problem = false;
for (const rel of h1Pages) {
    const text = read(rel);
    if (!text) continue;
    const count = (text.match(/<h1[\s>]/gi) || []).length;
    if (count > 1) {
        h1Problem = true;
        fail(`${rel}: ${count} тега <h1> — на странице должен быть один`);
    }
}
for (const rel of h1Pages.filter((name) => name.startsWith('preview/'))) {
    const count = ((read(rel) || '').match(/<h1[\s>]/gi) || []).length;
    if (count !== 1) {
        h1Problem = true;
        fail(`${rel}: ${count} тега <h1> — в собранной странице нужен один`);
    }
}
for (const rel of ['demo/index.php', 'preview/index.html']) {
    const text = read(rel) || '';
    const h1 = (text.match(/<h1[\s>]/gi) || []).length;
    const titles = (text.match(/class="hero-title"/g) || []).length;
    if (h1 !== 1 || titles !== 3) {
        h1Problem = true;
        fail(`${rel}: <h1> ${h1}, слайдов hero-title ${titles} (нужно 1 и 3)`);
    }
}
if (!h1Problem) {
    ok(`заголовки: один <h1> на страницу (${h1Pages.length} файлов)`);
}

/* ------------------------------------------------------------------ */
/* 10. PWA                                                            */
/* ------------------------------------------------------------------ */

const manifest = read('manifest.webmanifest');
if (manifest) {
    let parsed = null;
    try {
        parsed = JSON.parse(manifest);
    } catch (error) {
        fail(`manifest.webmanifest: невалидный JSON (${error.message})`);
    }
    if (parsed) {
        for (const field of ['name', 'short_name', 'start_url', 'display', 'theme_color', 'icons']) {
            if (!parsed[field]) {
                fail(`manifest.webmanifest: нет поля ${field}`);
            }
        }
        const sizes = new Set((parsed.icons || []).map((icon) => icon.sizes));
        for (const size of ['192x192', '512x512']) {
            if (!sizes.has(size)) {
                fail(`manifest.webmanifest: нет иконки ${size}`);
            }
        }
        if (!(parsed.icons || []).some((icon) => icon.purpose === 'maskable')) {
            warn('manifest.webmanifest: нет maskable-иконки');
        }
        ok(`manifest: иконок ${(parsed.icons || []).length}`);
    }
}

const sw = read('sw.js');
if (sw) {
    for (const token of ['CACHE', 'install', 'fetch', 'offline.html', 'notificationclick', 'showNotification']) {
        if (!sw.includes(token)) {
            fail(`sw.js: нет обязательного фрагмента «${token}»`);
        }
    }
    const version = sw.match(/const\s+VERSION\s*=\s*['"]([^'"]+)['"]/);
    if (!version) {
        warn('sw.js: не найден const VERSION (кеш не инвалидируется при правке)');
    }
    ok('sw.js: install/fetch/offline/push');
}

const header = read(`${tplRel}/header.php`);
if (header) {
    if (!header.includes('manifest.webmanifest')) {
        fail('header.php: нет ссылки на manifest.webmanifest');
    }
    if (!header.includes('nlb_seo_head()')) {
        fail('header.php: не вызывает nlb_seo_head()');
    }
    if (!header.includes('nlb_seo_jsonld()')) {
        fail('header.php: не вызывает nlb_seo_jsonld()');
    }
    if (/fonts\.googleapis\.com|fonts\.gstatic\.com/.test(header)) {
        fail('header.php: вернулся Google Fonts (внешний запрос)');
    }
    ok('header.php: manifest, SEO, без внешних шрифтов');
}

const footer = read(`${tplRel}/footer.php`);
if (footer) {
    for (const hook of ['nlbInstallBanner', 'nlbCookieNotice', 'pwa.js', 'consent.js', 'NLB_PUSH', 'push.js']) {
        if (!footer.includes(hook)) {
            fail(`footer.php: нет «${hook}»`);
        }
    }
    ok('footer.php: баннеры установки и cookie');
}

/* ------------------------------------------------------------------ */
/* 11. SEO и безопасность                                             */
/* ------------------------------------------------------------------ */

const robots = read('robots.txt');
if (robots) {
    for (const rule of ['Disallow: /bitrix/', 'Disallow: /db/', 'Disallow: /tools/', 'Sitemap:']) {
        if (!robots.includes(rule)) {
            fail(`robots.txt: нет правила «${rule}»`);
        }
    }
    ok('robots.txt: служебные каталоги закрыты');
}

const sitemap = read('sitemap.php');
if (sitemap) {
    for (const part of ['<urlset', '<loc>', 'lastmod']) {
        if (!sitemap.includes(part)) {
            fail(`sitemap.php: нет фрагмента ${part}`);
        }
    }
    const rewrites = read('urlrewrite.php');
    if (rewrites && !rewrites.includes('sitemap\\.xml')) {
        fail('urlrewrite.php: нет правила для /sitemap.xml');
    }
    ok('sitemap.php: urlset/loc/lastmod');
}

const security = read('local/php_interface/include/nlb_security.php');
if (security) {
    for (const headerName of [
        'X-Content-Type-Options',
        'X-Frame-Options',
        'Referrer-Policy',
        'Permissions-Policy',
        'Content-Security-Policy',
    ]) {
        if (!security.includes(headerName)) {
            fail(`nlb_security.php: нет заголовка ${headerName}`);
        }
    }
    ok('nlb_security.php: заголовки на месте');
}

const seo = read('local/php_interface/include/nlb_seo.php');
if (seo) {
    for (const part of ['rel="canonical"', 'og:title', 'twitter:card', 'application/ld+json']) {
        if (!seo.includes(part)) {
            fail(`nlb_seo.php: нет фрагмента ${part}`);
        }
    }
    ok('nlb_seo.php: canonical/OG/Twitter/JSON-LD');
}

const init = read('local/php_interface/init.php');
if (init) {
    for (const inc of ['nlb_security.php', 'nlb_db.php', 'nlb_seo.php']) {
        if (!init.includes(inc)) {
            fail(`init.php: не подключает ${inc}`);
        }
    }
    if (!init.includes('nlb_security_headers()')) {
        fail('init.php: не вызывает nlb_security_headers()');
    }
    ok('init.php: хелперы подключены');
}

/* ------------------------------------------------------------------ */
/* 11.1. Фильтр чата: браузер ↔ сервер                                */
/* ------------------------------------------------------------------ */

const chatFilter = read('local/php_interface/include/nlb_text_filter.php');
if (chatFilter) {
    for (const fn of ['nlb_filter_blocked', 'nlb_filter_sensitive', 'nlb_filter_study', 'nlb_filter_hit', 'nlb_text_problem']) {
        if (!chatFilter.includes(`function ${fn}(`)) {
            fail(`nlb_text_filter.php: нет ${fn}()`);
        }
    }
    if (!chatFilter.includes("'ненавиж'")) {
        fail('nlb_text_filter.php: «ненавиж» не в тематических корнях — «книги о ненависти» перестанут проходить');
    }
    ok('nlb_text_filter.php: списки и вердикт на месте');
}

const chatAjax = read('local/ajax/chat.php');
if (chatAjax) {
    if (!chatAjax.includes('nlb_text_filter.php')) {
        fail('local/ajax/chat.php: не подключает nlb_text_filter.php');
    }
    if (!chatAjax.includes('nlb_text_problem(')) {
        fail('local/ajax/chat.php: не вызывает nlb_text_problem()');
    }
    ok('local/ajax/chat.php: серверный фильтр подключён');
}

const chatScript = read(`${tplRel}/script.js`) || '';
for (const marker of ['/* nlb-chat-filter:start */', '/* nlb-chat-filter:end */']) {
    if (!chatScript.includes(marker)) {
        fail(`script.js: нет маркера ${marker} — гейт паритета не найдёт блок фильтра`);
    }
}
if (!chatScript.includes('const chatProblem = function')) {
    fail('script.js: нет chatProblem()');
}
ok('script.js: блок фильтра отделён маркерами');

const filterFixture = read('db/tests/fixtures/filter-cases.json');
if (filterFixture) {
    let filterCases = 0;
    try {
        filterCases = (JSON.parse(filterFixture).cases || []).length;
    } catch (error) {
        fail(`db/tests/fixtures/filter-cases.json: не JSON — ${error.message}`);
    }
    if (filterCases > 0 && filterCases < 40) {
        fail(`db/tests/fixtures/filter-cases.json: ${filterCases} фраз, нужно не меньше 40`);
    } else if (filterCases >= 40) {
        ok(`фильтр чата: общий набор фраз (${filterCases})`);
    }
}

/* ------------------------------------------------------------------ */
/* 11.2. Поиск по сайту: индекс свежий, эндпоинт и модалка связаны     */
/* ------------------------------------------------------------------ */

const searchLib = read('local/php_interface/include/nlb_search.php');
if (searchLib) {
    for (const fn of ['nlb_search_query', 'nlb_search_tokenize', 'nlb_search_build_index', 'nlb_search_source_hashes', 'nlb_search_load_index']) {
        if (!searchLib.includes(`function ${fn}(`)) {
            fail(`nlb_search.php: нет ${fn}()`);
        }
    }
    ok('nlb_search.php: ранжирование и сборка индекса на месте');
}

const searchAjax = read('local/ajax/search.php');
if (searchAjax) {
    if (!searchAjax.includes('nlb_search.php')) {
        fail('local/ajax/search.php: не подключает nlb_search.php');
    }
    if (!searchAjax.includes('nlb_search_api(')) {
        fail('local/ajax/search.php: не вызывает nlb_search_api() — контракт должен жить в библиотеке и проверяться самопроверкой');
    }
    if (!searchAjax.includes('search_unavailable')) {
        fail('local/ajax/search.php: нет ответа 503 search_unavailable — без библиотеки эндпоинт отдаст 200 с пустотой');
    }
    if (!searchAjax.includes('no-store')) {
        fail('local/ajax/search.php: нет Cache-Control: no-store — выдачу закеширует прокси');
    }
    ok('local/ajax/search.php: эндпоинт подключён к библиотеке');
}

const searchHeader = read(`${tplRel}/header.php`) || '';
if (searchHeader && !searchHeader.includes('id="nlbSearchInstant"')) {
    fail('header.php: нет контейнера #nlbSearchInstant — мгновенная выдача некуда выводить');
}
if (searchHeader && !searchHeader.includes('data-search-endpoint=')) {
    fail('header.php: у формы поиска нет data-search-endpoint — script.js не узнает адрес эндпоинта');
}
ok('header.php: контейнер мгновенной выдачи и адрес эндпоинта');

const searchScript = read(`${tplRel}/script.js`) || '';
if (searchScript) {
    for (const part of ['#nlbSearchInstant', 'AbortController', 'textContent', 'data-search-endpoint']) {
        if (!searchScript.includes(part)) {
            fail(`script.js: в блоке мгновенного поиска нет ${part}`);
        }
    }
    if (/innerHTML\s*=/.test(searchScript.slice(searchScript.indexOf('#nlbSearchInstant')))) {
        fail('script.js: выдача поиска собирается через innerHTML — только textContent, иначе XSS из текста документа');
    }
    ok('script.js: мгновенный поиск через textContent и AbortController');
}

const searchStyles = read(`${tplRel}/styles.css`) || '';
if (searchStyles && !searchStyles.includes('.search-instant__item')) {
    fail('styles.css: нет стилей .search-instant__item');
} else if (searchStyles) {
    ok('styles.css: стили мгновенной выдачи');
}

const searchFixture = read('db/tests/fixtures/search-cases.json');
if (searchFixture) {
    let searchCases = 0;
    try {
        searchCases = (JSON.parse(searchFixture).cases || []).length;
    } catch (error) {
        fail(`db/tests/fixtures/search-cases.json: не JSON — ${error.message}`);
    }
    if (searchCases > 0 && searchCases < 20) {
        fail(`db/tests/fixtures/search-cases.json: ${searchCases} запросов, нужно не меньше 20`);
    } else if (searchCases >= 20) {
        ok(`поиск по сайту: набор запросов (${searchCases})`);
    }
}

const searchIndexRaw = read('db/search/index.json');
if (searchIndexRaw) {
    let index = null;
    try {
        index = JSON.parse(searchIndexRaw);
    } catch (error) {
        fail(`db/search/index.json: не JSON — ${error.message}`);
    }

    if (index) {
        if (!Array.isArray(index.docs) || index.docs.length === 0) {
            fail('db/search/index.json: нет документов');
        }
        if (!index.terms || Object.keys(index.terms).length === 0) {
            fail('db/search/index.json: нет словаря термов');
        }
        if (index.docs_count !== (index.docs || []).length) {
            fail('db/search/index.json: docs_count не совпадает с числом документов');
        }

        const dataFiles = fs.existsSync(path.join(root, 'db/config/data'))
            ? fs.readdirSync(path.join(root, 'db/config/data')).filter((name) => name.endsWith('.php')).sort()
            : [];
        const expected = [
            ...dataFiles.map((name) => `db/config/data/${name}`),
            'db/config/iblocks.php',
            'db/search/pages.json',
        ];

        const computed = {};
        for (const relative of expected) {
            const abs = path.join(root, relative);
            computed[relative] = fs.existsSync(abs)
                ? crypto.createHash('sha256').update(fs.readFileSync(abs)).digest('hex')
                : '';
        }

        const stored = index.sources && typeof index.sources === 'object' ? index.sources : {};
        const stale = [];
        for (const relative of expected) {
            if (stored[relative] !== computed[relative]) stale.push(relative);
        }
        for (const relative of Object.keys(stored)) {
            if (!expected.includes(relative)) stale.push(`${relative} (исчез)`);
        }

        if (stale.length) {
            fail(
                'db/search/index.json устарел, php tools/build-search-index.php не запускали после правки: '
                + stale.join(', ')
            );
        } else {
            const total = crypto.createHash('sha256').update(JSON.stringify(computed)).digest('hex');
            if (index.source_hash !== total) {
                fail('db/search/index.json: source_hash не совпадает с отпечатками sources');
            } else {
                ok(`индекс поиска свежий: ${(index.docs || []).length} документов, ${Object.keys(index.terms || {}).length} термов`);
            }
        }
    }
}

/* ------------------------------------------------------------------ */
/* 12. Меню и база данных                                             */
/* ------------------------------------------------------------------ */

const topMenu = read('db/menu/top.menu.php');
if (topMenu) {
    const items = [...topMenu.matchAll(/^\s*\['([^']+)',\s*'([^']+)'/gm)];
    if (items.length !== 7) {
        fail(`db/menu/top.menu.php: ${items.length} пунктов вместо 7`);
    }
    if (topMenu.includes("'/map/'")) {
        fail('db/menu/top.menu.php: есть пункт /map/ — исключён из объёма');
    }
    for (const [, label, url] of items) {
        if (url === '/') continue;
        const dir = url.replace(/^\/|\/$/g, '');
        if (!fs.existsSync(path.join(root, dir, 'index.php'))) {
            fail(`db/menu/top.menu.php: пункт «${label}» ведёт на ${url}, страницы нет`);
        }
    }
    ok(`меню top: ${items.length} пунктов, все страницы на месте`);
}

const headerMenuPhp = read(`${tplRel}/header.php`);
if (headerMenuPhp && !headerMenuPhp.includes("'MAX_LEVEL' => '2'")) {
    fail('header.php: bitrix:menu без MAX_LEVEL = 2 — второй уровень не подключён');
}

const menuTemplate = read(`${tplRel}/components/bitrix/menu/top_bootstrap/template.php`);
if (!menuTemplate) {
    fail('нет шаблона меню top_bootstrap/template.php');
} else {
    for (const part of ['DEPTH_LEVEL', 'dropdown-menu', 'dropdown-item', 'data-bs-toggle="dropdown"']) {
        if (!menuTemplate.includes(part)) {
            fail(`шаблон меню top_bootstrap: нет фрагмента ${part}`);
        }
    }
    ok('шаблон меню: второй уровень и выпадающие списки');
}

for (const section of ['services', 'printing']) {
    const rel = `db/menu/${section}.left.menu.php`;
    const text = read(rel);
    const data = read(`db/config/data/${section}.php`) || '';
    if (!text) {
        fail(`нет файла меню: ${rel}`);
        continue;
    }
    if (!fs.existsSync(path.join(root, section, 'index.php'))) {
        fail(`${section}/index.php: нет страницы каталога`);
    }
    const links = [...text.matchAll(/^\s*\['([^']+)',\s*'([^']+)'/gm)];
    let details = 0;
    for (const [, label, url] of links) {
        if (url === `/${section}/`) continue;
        details++;
        // Детальные страницы работают через detail.php и ЧПУ: каталога
        // /services/<код>/ на диске нет, поэтому сверяем код с демо-данными.
        const code = url.replace(/\/+$/, '').split('/').pop();
        if (!data.includes(`'code' => '${code}'`)) {
            fail(`${rel}: пункт «${label}» ведёт на ${url}, в демо-данных нет кода ${code}`);
        }
    }
    if (links.length !== 9 || details !== 8) {
        fail(`${rel}: ${links.length} пунктов, из них ${details} ссылок на услуги (нужно 9 и 8)`);
    }
}
ok('меню разделов: услуги и печать, 8 ссылок в каждом');

const homePreview = read('preview/index.html') || '';
if ((homePreview.match(/nav-item dropdown/g) || []).length !== 2) {
    fail('preview/index.html: нет двух выпадающих пунктов верхнего меню');
}
if (!homePreview.includes('services/observation-deck/') || !homePreview.includes('printing/pechat-dokumentov/')) {
    fail('preview/index.html: в выпадающих списках нет ссылок на услуги');
}
const menuStyles = read(`${tplRel}/styles.css`) || '';
if (!menuStyles.includes('.dropdown-menu') || !menuStyles.includes('.dropdown-item')) {
    fail('styles.css: нет стилей выпадающего меню');
}
// Регрессия: если клик по .dropdown-toggle закрывает collapse, на телефоне
// «Услуги» и «Печать» сворачивают всё меню вместо показа подпунктов.
for (const rel of [`${tplRel}/script.js`, 'preview/script.js']) {
    const text = read(rel) || '';
    if (text && !text.includes('a.nav-link:not(.dropdown-toggle)')) {
        fail(`${rel}: обработчик мобильного меню закрывает пункты с подменю — нужен a.nav-link:not(.dropdown-toggle)`);
    }
}
ok('preview: выпадающее меню в шапке, мобильный обработчик не трогает подменю');

const migrations = fs.existsSync(path.join(root, 'db/migrations'))
    ? fs.readdirSync(path.join(root, 'db/migrations')).filter((name) => name.endsWith('.php'))
    : [];
if (migrations.length < 7) {
    fail(`db/migrations: ${migrations.length} миграций вместо 7`);
} else {
    ok(`миграций: ${migrations.length}`);
}

for (const rel of ['db/.htaccess', 'tools/.htaccess']) {
    const text = read(rel);
    if (text && !/deny|denied/i.test(text)) {
        fail(`${rel}: нет запрета доступа`);
    }
}
ok('db/ и tools/ закрыты от веба');

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
