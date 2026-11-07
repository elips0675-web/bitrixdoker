<?php
/**
 * Правила ЧПУ для детальных страниц.
 *
 * Формат стандартный для Битрикса: файл читает ядро при обработке адреса.
 * Правила можно также увидеть и изменить в админке:
 * «Настройки → Настройки продукта → Сайты → Правила обработки адресов».
 *
 * Схема: /<раздел>/<код элемента>/ → /<раздел>/detail.php?CODE=<код>
 * Параметры строки запроса передаются в detail.php без потери:
 * /services/<код>/?utm_source=... → CODE=<код>&utm_source=...
 */

$arUrlRewrite = [
    [
        'CONDITION' => '#^/sitemap\.xml$#i',
        'RULE' => '',
        'ID' => '',
        'PATH' => '/sitemap.php',
        'SORT' => 10,
    ],
    [
        'CONDITION' => '#^/news/([a-z0-9_-]+)/?(?:\?(.*))?$#i',
        'RULE' => 'CODE=$1&$2',
        'ID' => '',
        'PATH' => '/news/detail.php',
        'SORT' => 100,
    ],
    [
        'CONDITION' => '#^/events/([a-z0-9_-]+)/?(?:\?(.*))?$#i',
        'RULE' => 'CODE=$1&$2',
        'ID' => '',
        'PATH' => '/events/detail.php',
        'SORT' => 110,
    ],
    [
        'CONDITION' => '#^/services/([a-z0-9_-]+)/?(?:\?(.*))?$#i',
        'RULE' => 'CODE=$1&$2',
        'ID' => '',
        'PATH' => '/services/detail.php',
        'SORT' => 120,
    ],
    [
        'CONDITION' => '#^/printing/([a-z0-9_-]+)/?(?:\?(.*))?$#i',
        'RULE' => 'CODE=$1&$2',
        'ID' => '',
        'PATH' => '/printing/detail.php',
        'SORT' => 130,
    ],
    [
        'CONDITION' => '#^/books/([a-z0-9_-]+)/?(?:\?(.*))?$#i',
        'RULE' => 'CODE=$1&$2',
        'ID' => '',
        'PATH' => '/books/detail.php',
        'SORT' => 140,
    ],
    [
        'CONDITION' => '#^/collections/([a-z0-9_-]+)/?(?:\?(.*))?$#i',
        'RULE' => 'CODE=$1&$2',
        'ID' => '',
        'PATH' => '/collections/detail.php',
        'SORT' => 150,
    ],
];
