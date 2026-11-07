<?php
/**
 * Сборка индекса поиска по сайту (BM25).
 *
 * Запуск: php tools/build-search-index.php
 *         php tools/build-search-index.php --check
 *
 * Что делает: собирает документы, строит индекс и пишет
 * `db/search/index.json`. Источники — демо-данные инфоблоков
 * (`db/config/data/*.php` + шаблоны ссылок из `db/config/iblocks.php`)
 * и курируемые страницы (`db/search/pages.json`). На живом ядре те же
 * документы берутся из инфоблоков (`nlb_search_documents_from_bitrix()`),
 * поэтому после установки Битрикса команда не меняется.
 *
 * `--check` ничего не пишет: сверяет отпечатки источников с индексом и
 * возвращает 1, если индекс устарел. Это же правило проверяет раздел 11.2
 * в `tools/check-template.mjs` — гейт читает пофайловые хеши, чтобы
 * правка данных без пересборки не проходила молча.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

define('B_PROLOG_INCLUDED', true);

$root = dirname(__DIR__);

require $root . '/local/php_interface/include/nlb_search.php';

$checkOnly = in_array('--check', $argv ?? [], true);

if ($checkOnly) {
    $index = nlb_search_load_index($root);
    if ($index === null) {
        fwrite(STDERR, "Индекса нет: соберите php tools/build-search-index.php\n");
        exit(1);
    }

    $actual = nlb_search_source_hashes($root);
    $stored = is_array($index['sources'] ?? null) ? $index['sources'] : [];
    $stale = [];

    foreach ($actual as $relative => $hash) {
        if (($stored[$relative] ?? null) !== $hash) {
            $stale[] = $relative;
        }
    }
    foreach (array_keys($stored) as $relative) {
        if (!array_key_exists($relative, $actual)) {
            $stale[] = $relative . ' (исчез)';
        }
    }

    if ($stale !== []) {
        echo "Индекс устарел:\n";
        foreach (array_unique($stale) as $relative) {
            echo "  x {$relative}\n";
        }
        echo "Пересоберите: php tools/build-search-index.php\n";
        exit(1);
    }

    echo 'Индекс соответствует источникам: документов ' . (int)($index['docs_count'] ?? 0)
        . ', собран ' . (string)($index['built_at'] ?? '') . ".\n";
    exit(0);
}

$documents = nlb_search_documents_from_bitrix($root);
$source = 'инфоблоки';

if ($documents === []) {
    $documents = nlb_search_documents_from_data($root);
    $source = 'демо-данные';
}

$pages = nlb_search_documents_from_pages($root);
$documents = array_merge($documents, $pages);

if ($documents === []) {
    fwrite(STDERR, "Нет документов для индекса: проверьте db/config/data/ и db/search/pages.json.\n");
    exit(1);
}

$index = nlb_search_build_index($documents, $root);

if (!nlb_search_save_index($index, $root)) {
    fwrite(STDERR, "Не удалось записать db/search/index.json\n");
    exit(1);
}

$path = nlb_search_index_path($root);
$size = is_file($path) ? (int)filesize($path) : 0;
$terms = is_array($index['terms']) ? count($index['terms']) : 0;

echo "Индекс поиска собран.\n";
echo '  источник: ' . $source . ' + страницы (' . count($pages) . ")\n";
echo '  документов: ' . count($index['docs']) . "\n";
echo '  термов: ' . $terms . "\n";
echo '  средняя длина: ' . $index['avgdl'] . " слов\n";
echo '  k1 = ' . $index['k1'] . ', b = ' . $index['b'] . "\n";
echo '  файл: db/search/index.json (' . number_format($size / 1024, 1, ',', ' ') . " КБ)\n";
echo '  отпечаток источников: ' . substr((string)$index['source_hash'], 0, 12) . "\n";
