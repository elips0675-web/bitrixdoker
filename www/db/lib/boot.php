<?php
/**
 * NLB Bootstrap — загрузка окружения для миграций.
 *
 * Работает в двух режимах:
 *  - CLI:  php db/run.php
 *  - веб:  открыть /db/run.php под администратором (аварийный вариант,
 *          если на хостинге нет CLI);
 *
 * Корень сайта определяется так: переменная окружения NLB_ROOT, иначе
 * каталог на два уровня выше (db/lib → db → корень сайта).
 */

declare(strict_types=1);

if (defined('NLB_DB_BOOT')) {
    return;
}
define('NLB_DB_BOOT', true);

if (!function_exists('nlb_root')) {
    function nlb_root(): string
    {
        $root = getenv('NLB_ROOT');
        if ($root === false || $root === '') {
            $root = dirname(__DIR__, 2);
        }
        return rtrim(str_replace('\\', '/', (string)$root), '/');
    }
}

if (!function_exists('nlb_is_cli')) {
    function nlb_is_cli(): bool
    {
        return PHP_SAPI === 'cli';
    }
}

if (!function_exists('nlb_log_path')) {
    function nlb_log_path(): string
    {
        if (!isset($GLOBALS['NLB_LOG_FILE'])) {
            $dir = nlb_root() . '/db/logs';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $GLOBALS['NLB_LOG_FILE'] = $dir . '/run-' . date('Ymd-His') . '.log';
        }
        return (string)$GLOBALS['NLB_LOG_FILE'];
    }
}

if (!function_exists('nlb_say')) {
    /**
     * Печать строки в консоль/браузер и в лог запуска.
     */
    function nlb_say(string $line = ''): void
    {
        echo $line . "\n";
        @file_put_contents(nlb_log_path(), $line . "\n", FILE_APPEND);
    }
}

/*
 * Веб-режим: prolog сам поднимет сессию, поэтому проверку прав делаем
 * после загрузки, а до этого выводим только текст.
 */
$nlbRoot = nlb_root();
$nlbProlog = $nlbRoot . '/bitrix/modules/main/include/prolog_before.php';

if (!is_file($nlbProlog)) {
    $message = 'NLB: не найдено ядро Битрикса. Ожидался файл ' . $nlbProlog
        . '. Скопируйте каталог db/ в корень сайта (рядом с bitrix/) '
        . 'или задайте NLB_ROOT.';
    if (nlb_is_cli()) {
        fwrite(STDERR, $message . "\n");
    } else {
        http_response_code(500);
        echo '<pre>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
    }
    exit(2);
}

$_SERVER['DOCUMENT_ROOT'] = $nlbRoot;
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
define('STOP_STATISTICS', true);

if (nlb_is_cli()) {
    define('BX_CRONTAB', true);
    @set_time_limit(0);
}

require_once $nlbProlog;

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    $message = 'NLB: не удалось подключить модуль iblock.';
    if (nlb_is_cli()) {
        fwrite(STDERR, $message . "\n");
    } else {
        echo '<pre>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
    }
    exit(2);
}

require_once __DIR__ . '/helpers.php';

if (!function_exists('nlb_require_admin')) {
    /**
     * В веб-режиме миграции доступны только администратору.
     */
    function nlb_require_admin(): void
    {
        if (nlb_is_cli()) {
            return;
        }
        global $USER;
        if (!is_object($USER) || !$USER->IsAdmin()) {
            http_response_code(403);
            echo '<pre>NLB: запуск миграций доступен только администратору '
                . 'или из командной строки.</pre>';
            exit(3);
        }
    }
}
