<?php
/**
 * Карта сайта для поисковых систем (XML).
 *
 * Отдаётся по адресу /sitemap.xml через правило в urlrewrite.php.
 * Домен берётся из запроса, поэтому файл не нужно править при переносе на прод.
 *
 * Что попадает в карту: статические разделы сайта. Элементы инфоблоков
 * (новости, события, книги…) сюда не перечисляются — их добавляет штатный
 * генератор Битрикса («Настройки → Поиск → Карта сайта») после установки ядра,
 * иначе список пришлось бы дублировать вручную и он бы рассинхронизировался
 * с базой. Это записано в docs/security-performance.md, §2.
 *
 * Ядро не подключается: файл работает даже на пустом шаблоне и не тратит
 * ресурсы на пролог.
 */

declare(strict_types=1);

header('Content-Type: application/xml; charset=UTF-8');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $forwarded = strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']);
    if ($forwarded === 'https' || $forwarded === 'http') {
        $scheme = $forwarded;
    }
}

$host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : 'example.by';
$base = $scheme . '://' . $host;

/**
 * Раздел => файл, по дате изменения которого считается lastmod.
 * Порядок совпадает с меню и картой сайта.
 */
$sections = [
    '/'             => 'index.php',
    '/about/'       => 'about/index.php',
    '/news/'        => 'news/index.php',
    '/events/'      => 'events/index.php',
    '/collections/' => 'collections/index.php',
    '/services/'    => 'services/index.php',
    '/printing/'    => 'printing/index.php',
    '/books/'       => 'books/index.php',
    '/contacts/'    => 'contacts/index.php',
    '/feedback/'    => 'feedback/index.php',
    '/subscribe/'   => 'subscribe/index.php',
    '/search/'      => 'search/index.php',
    '/rules/'       => 'rules/index.php',
    '/privacy/'     => 'privacy/index.php',
    '/terms/'       => 'terms/index.php',
    '/sitemap/'     => 'sitemap/index.php',
];

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');

/**
 * Приоритет: главная и разделы меню важнее служебных страниц.
 */
$priority = [
    '/'             => '1.0',
    '/news/'        => '0.8',
    '/events/'      => '0.8',
    '/books/'       => '0.8',
    '/services/'    => '0.8',
    '/printing/'    => '0.8',
    '/collections/' => '0.7',
    '/contacts/'    => '0.7',
    '/about/'       => '0.6',
    '/feedback/'    => '0.5',
    '/subscribe/'   => '0.4',
    '/rules/'       => '0.3',
    '/privacy/'     => '0.3',
    '/terms/'       => '0.3',
    '/sitemap/'     => '0.3',
    '/search/'      => '0.1',
];

$escape = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
};

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($sections as $path => $file) {
    // Страница поиска закрыта в robots.txt — в карту её не кладём.
    if ($path === '/search/') {
        continue;
    }

    $absolute = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
    $lastmod = is_file($absolute) ? date('Y-m-d', (int)filemtime($absolute)) : date('Y-m-d');

    echo "  <url>\n";
    echo '    <loc>' . $escape($base . $path) . "</loc>\n";
    echo '    <lastmod>' . $escape($lastmod) . "</lastmod>\n";
    echo '    <changefreq>' . ($path === '/' ? 'daily' : 'weekly') . "</changefreq>\n";
    echo '    <priority>' . $escape($priority[$path] ?? '0.5') . "</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>' . "\n";
