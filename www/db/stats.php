<?php
/**
 * Факты о проекте для гейта документации.
 *
 * Считает то, о чём пишут документы, из кода и каталогов: конфиг инфоблоков,
 * демо-данные, каталог миграций, размеры фронта, индекс поиска, наборы фраз.
 * Документы эти числа не задают, поэтому расхождение видно сразу.
 *
 * Запуск: php db/stats.php          — строки «ключ: значение»
 *         php db/stats.php --json   — один JSON-объект для гейта
 * Выход:  0 — посчитано, 1 — не хватает файла или каталога.
 */

$base = dirname(__DIR__);
$errors = [];

/**
 * Посчитать элементы демо-контента и проверить, что файлы читаются.
 *
 * @param string $dir
 * @param array<int,string> $errors
 * @return int
 */
function nlb_stats_elements(string $dir, array &$errors): int
{
    $total = 0;
    $files = glob($dir . '/*.php') ?: [];
    sort($files);
    if ($files === []) {
        $errors[] = 'нет файлов демо-контента: db/config/data/*.php';
        return 0;
    }
    foreach ($files as $file) {
        $data = require $file;
        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            $errors[] = 'data/' . basename($file) . ': нет ключа items';
            continue;
        }
        $total += count($data['items']);
    }
    return $total;
}

/**
 * Посчитать PHP-файлы проекта вне статического превью.
 *
 * @param string $base
 * @param array<int,string> $errors
 * @return int
 */
function nlb_stats_php_files(string $base, array &$errors): int
{
    if (!is_dir($base . '/local')) {
        $errors[] = 'нет каталога local/';
        return 0;
    }
    $total = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
        if (str_starts_with($relative, 'preview/')) {
            continue;
        }
        $total++;
    }
    return $total;
}

/**
 * Число строк в файлах заданного расширения вне статического превью.
 *
 * Считается как число переводов строки плюс последняя строка без перевода —
 * ровно то, что покажет редактор, а не «файл минус один».
 *
 * @param string $base
 * @param string $extension
 * @param array<int,string> $errors
 * @return int
 */
function nlb_stats_lines(string $base, string $extension, array &$errors): int
{
    if (!is_dir($base)) {
        $errors[] = 'нет каталога: ' . basename($base);
        return 0;
    }
    $total = 0;
    $found = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== $extension) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
        if (str_starts_with($relative, 'preview/')) {
            continue;
        }
        $content = (string)file_get_contents($file->getPathname());
        $found++;
        $total += substr_count($content, "\n");
        if ($content !== '' && !str_ends_with($content, "\n")) {
            $total++;
        }
    }
    if ($found === 0) {
        $errors[] = 'нет файлов .' . $extension . ' в ' . basename($base);
    }
    return $total;
}

/**
 * Число строк одного файла.
 *
 * @param string $path
 * @param array<int,string> $errors
 * @return int
 */
function nlb_stats_file_lines(string $path, array &$errors): int
{
    if (!is_file($path)) {
        $errors[] = 'нет файла: ' . basename($path);
        return 0;
    }
    $content = (string)file_get_contents($path);
    $lines = substr_count($content, "\n");
    if ($content !== '' && !str_ends_with($content, "\n")) {
        $lines++;
    }
    return $lines;
}

/**
 * Число пройденных проверок в самопроверке.
 *
 * Скрипт действительно запускается: число в документации должно совпадать
 * с тем, что прошло, а не с тем, сколько вызовов нашлось в исходнике.
 *
 * @param string $script
 * @param array<int,string> $errors
 * @return int
 */
function nlb_stats_checks(string $script, array &$errors): int
{
    if (!is_file($script)) {
        $errors[] = 'нет самопроверки: ' . basename($script);
        return 0;
    }
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' 2>&1';
    $output = (string)shell_exec($command);
    if (!preg_match('/Все проверки пройдены[:\s(]+(\d+)/u', $output, $match)) {
        $errors[] = 'самопроверка не отдала итог: ' . basename($script);
        return 0;
    }
    return (int)$match[1];
}

/**
 * Число файлов заданного расширения в каталоге (без рекурсии).
 *
 * @param string $dir
 * @param string $extension
 * @param array<int,string> $errors
 * @return int
 */
function nlb_stats_dir_files(string $dir, string $extension, array &$errors): int
{
    if (!is_dir($dir)) {
        $errors[] = 'нет каталога: ' . basename($dir);
        return 0;
    }
    $files = glob($dir . '/*.' . $extension) ?: [];
    return count($files);
}

/**
 * Число файлов заданного расширения в каталоге и подкаталогах.
 *
 * @param string $dir
 * @param string $extension
 * @param array<int,string> $errors
 * @return int
 */
function nlb_stats_tree_files(string $dir, string $extension, array &$errors): int
{
    if (!is_dir($dir)) {
        $errors[] = 'нет каталога: ' . basename($dir);
        return 0;
    }
    $total = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === $extension) {
            $total++;
        }
    }
    return $total;
}

/**
 * Количество записей в наборе фраз (JSON с ключом cases).
 *
 * @param string $path
 * @param array<int,string> $errors
 * @return int
 */
function nlb_stats_cases(string $path, array &$errors): int
{
    if (!is_file($path)) {
        $errors[] = 'нет файла: ' . basename($path);
        return 0;
    }
    $data = json_decode((string)file_get_contents($path), true);
    if (!is_array($data) || !isset($data['cases']) || !is_array($data['cases'])) {
        $errors[] = basename($path) . ': нет ключа cases';
        return 0;
    }
    return count($data['cases']);
}

$config = require $base . '/db/config/iblocks.php';
$iblocks = $config['iblocks'] ?? [];
$properties = 0;
$sections = 0;
foreach ($iblocks as $iblock) {
    $properties += count($iblock['properties'] ?? []);
    $sections += count($iblock['sections_data'] ?? []);
}

$migrationFiles = glob($base . '/db/migrations/*.php') ?: [];
if ($migrationFiles === []) {
    $errors[] = 'нет миграций: db/migrations/*.php';
}

$tpl = $base . '/local/templates/nlb_bootstrap';
$cssPath = $tpl . '/styles.css';
$jsPath = $tpl . '/script.js';
foreach ([$cssPath, $jsPath] as $path) {
    if (!is_file($path)) {
        $errors[] = 'нет файла: ' . str_replace($base . '/', '', $path);
    }
}

$css = is_file($cssPath) ? (string)file_get_contents($cssPath) : '';
$cssLines = substr_count($css, "\n");
if ($css !== '' && !str_ends_with($css, "\n")) {
    $cssLines++;
}

$indexPath = $base . '/db/search/index.json';
$index = is_file($indexPath) ? json_decode((string)file_get_contents($indexPath), true) : null;
if (!is_array($index)) {
    $errors[] = 'нет индекса поиска: db/search/index.json';
    $index = [];
}

$facts = [
    'types' => count($config['types'] ?? []),
    'iblocks' => count($iblocks),
    'properties' => $properties,
    'elements' => nlb_stats_elements($base . '/db/config/data', $errors),
    'sections' => $sections,
    'migrations' => count($migrationFiles),
    'php_files' => nlb_stats_php_files($base, $errors),
    'css_lines' => $cssLines,
    'css_bytes' => strlen($css),
    'js_bytes' => is_file($jsPath) ? (int)filesize($jsPath) : 0,
    'index_docs' => count($index['docs'] ?? []),
    'index_terms' => count($index['terms'] ?? []),
    'filter_cases' => nlb_stats_cases($base . '/db/tests/fixtures/filter-cases.json', $errors),
    'search_cases' => nlb_stats_cases($base . '/db/tests/fixtures/search-cases.json', $errors),
    'php_lines' => nlb_stats_lines($base, 'php', $errors),
    'script_js_lines' => nlb_stats_file_lines($jsPath, $errors),
    'docs_md' => nlb_stats_dir_files($base . '/docs', 'md', $errors),
    'preview_html' => nlb_stats_tree_files($base . '/preview', 'html', $errors),
    'db_tests' => nlb_stats_checks($base . '/db/tests/run.php', $errors),
    'filter_checks' => nlb_stats_checks($base . '/tools/filter-selftest.php', $errors),
    'search_checks' => nlb_stats_checks($base . '/tools/search-selftest.php', $errors),
];

if ($errors !== []) {
    fwrite(STDERR, "db/stats.php: факты не посчитаны\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '  - ' . $error . "\n");
    }
    exit(1);
}

if (in_array('--json', $argv, true)) {
    echo json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

foreach ($facts as $key => $value) {
    echo $key . ': ' . $value . "\n";
}
exit(0);
