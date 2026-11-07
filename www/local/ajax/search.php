<?php
/**
 * Поиск по сайту (BM25): JSON для мгновенной выдачи в модальном окне.
 *
 * GET q=<запрос> [limit=1..10]
 *
 * Ответ всегда JSON:
 *   200 { ok: true, query: "...", count: N, results: [ { id, title, url, category, score, snippet } ] }
 *   400 { ok: false, error: 'empty_query' | 'query_too_long' }
 *   405 { ok: false, error: 'method_not_allowed' }
 *   503 { ok: false, error: 'index_unavailable' | 'search_unavailable' }
 *
 * Зачем: штатный поиск Битрикса ищет по своему индексу и на демо-стенде не
 * работает вовсе (нет ядра). Здесь релевантность считается по BM25
 * (`local/php_interface/include/nlb_search.php`) из готового индекса
 * `db/search/index.json`, который собирает `tools/build-search-index.php`.
 *
 * При недоступности индекса форма в шапке продолжает работать как обычная:
 * она отправляет запрос в `/search/`, а мгновенная выдача просто не
 * появляется. Поэтому поиск не ломается целиком из-за отсутствия файла.
 *
 * Текст запроса не логируется: это персональные интересы посетителя.
 * Списки и математика проверяются `php tools/search-selftest.php`.
 */

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', false);
define('STOP_STATISTICS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$respond = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$library = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/nlb_search.php';
if (!is_file($library)) {
    $respond(['ok' => false, 'error' => 'search_unavailable'], 503);
}

require_once $library;

if (!function_exists('nlb_search_query') || !function_exists('nlb_search_load_index')) {
    $respond(['ok' => false, 'error' => 'search_unavailable'], 503);
}

if (!function_exists('nlb_search_api')) {
    $respond(['ok' => false, 'error' => 'search_unavailable'], 503);
}

$response = nlb_search_api($_GET, (string)($_SERVER['REQUEST_METHOD'] ?? ''), $_SERVER['DOCUMENT_ROOT']);
$respond($response['body'], $response['status']);
