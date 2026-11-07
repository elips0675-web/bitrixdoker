<?php
/**
 * Приём push-подписок браузера.
 *
 * POST action=subscribe   { subscription: PushSubscriptionJSON }
 * POST action=unsubscribe { endpoint: string }
 *
 * Подписка привязана к авторизованному пользователю: анонимных подписок нет.
 * Сотрудники (администраторы) получают роль staff и уведомления о бронях.
 *
 * Ответ всегда JSON: { ok: bool, ... }.
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

if (!function_exists('nlb_push_subscribe')) {
    $respond(['ok' => false, 'error' => 'push_disabled'], 503);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $respond(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

if (!check_bitrix_sessid()) {
    $respond(['ok' => false, 'error' => 'bad_sessid'], 403);
}

if (!$USER->IsAuthorized()) {
    $respond(['ok' => false, 'error' => 'auth_required'], 401);
}

if (!nlb_push_enabled()) {
    $respond(['ok' => false, 'error' => 'push_disabled'], 503);
}

$action = (string)($_POST['action'] ?? '');
$role = $USER->IsAdmin() ? 'staff' : 'client';
$email = (string)$USER->GetEmail();

if ($action === 'subscribe') {
    $raw = (string)($_POST['subscription'] ?? '');
    $subscription = json_decode($raw, true);
    if (!is_array($subscription)) {
        $respond(['ok' => false, 'error' => 'bad_subscription'], 400);
    }

    if (!nlb_push_subscribe($subscription, $role, $email)) {
        $respond(['ok' => false, 'error' => 'bad_subscription'], 400);
    }

    $respond(['ok' => true, 'role' => $role]);
}

if ($action === 'unsubscribe') {
    $endpoint = trim((string)($_POST['endpoint'] ?? ''));
    if ($endpoint === '') {
        $respond(['ok' => false, 'error' => 'bad_endpoint'], 400);
    }

    nlb_push_unsubscribe($endpoint);
    $respond(['ok' => true]);
}

$respond(['ok' => false, 'error' => 'unknown_action'], 400);
