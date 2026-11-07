<?php
/**
 * NLB Bootstrap — инициализация сайта.
 *
 * Подключается ядром автоматически, если каталог local/ разрешён.
 * Здесь только чтение данных и заголовки ответа: миграции и запись — в db/.
 *
 * Порядок важен:
 *   1. заголовки безопасности — до любого вывода;
 *   2. хелперы данных, медиа и SEO — их используют шаблон и страницы.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

require_once __DIR__ . '/include/nlb_security.php';
require_once __DIR__ . '/include/nlb_db.php';
require_once __DIR__ . '/include/nlb_forms.php';
require_once __DIR__ . '/include/nlb_push.php';
require_once __DIR__ . '/include/nlb_media.php';
require_once __DIR__ . '/include/nlb_seo.php';

\Bitrix\Main\Loader::includeModule('iblock');

nlb_security_headers();
