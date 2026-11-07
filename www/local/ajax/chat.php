<?php
/**
 * Серверная проверка сообщения чата.
 *
 * POST message=<текст> [sessid=<токен>]
 *
 * Ответ всегда JSON:
 *   200 { ok: true,  verdict: '' }
 *   400 { ok: false, error: 'empty_message' }
 *   403 { ok: false, error: 'bad_sessid' }
 *   405 { ok: false, error: 'method_not_allowed' }
 *   413 { ok: false, error: 'too_long' }
 *   422 { ok: false, error: 'rude'|'spam'|'gibberish', verdict: '...' }
 *   503 { ok: false, error: 'filter_unavailable' }
 *
 * Зачем: браузерный `chatProblem()` (`script.js`) отбивает мат, рекламу, ссылки
 * и бессмыслицу только в интерфейсе — его обходит отключённый JS или запрос
 * мимо формы. Здесь тот же вердикт считается до того, как текст попадёт
 * в поиск, в очередь оператору или в лог. Текст не сохраняется и не пишется
 * в журнал: в логах не должно быть сообщений посетителя.
 *
 * Списки и вердикты обязаны совпадать с браузерными: это проверяет
 * `node tools/check-filter-parity.mjs` на общем наборе фраз
 * `db/tests/fixtures/filter-cases.json`, сам фильтр — `php tools/filter-selftest.php`.
 *
 * Вызывается, когда включён бэкенд ответов (`USE_REAL_AI = true` в `script.js`,
 * P1-17 п.1). Сейчас бэкенда нет, поэтому в браузере эндпоинт не задействован.
 */

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', false);
define('STOP_STATISTICS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$respond = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$filter = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/nlb_text_filter.php';
if (!is_file($filter)) {
    $respond(['ok' => false, 'error' => 'filter_unavailable'], 503);
}

require_once $filter;

if (!function_exists('nlb_text_problem')) {
    $respond(['ok' => false, 'error' => 'filter_unavailable'], 503);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $respond(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

if (!check_bitrix_sessid()) {
    $respond(['ok' => false, 'error' => 'bad_sessid'], 403);
}

$message = trim((string)($_POST['message'] ?? ''));
if ($message === '') {
    $respond(['ok' => false, 'error' => 'empty_message'], 400);
}

if (mb_strlen($message, 'UTF-8') > 2000) {
    $respond(['ok' => false, 'error' => 'too_long'], 413);
}

$verdict = nlb_text_problem($message);

if ($verdict === '') {
    $respond(['ok' => true, 'verdict' => '']);
}

$respond(['ok' => false, 'error' => $verdict, 'verdict' => $verdict], 422);
