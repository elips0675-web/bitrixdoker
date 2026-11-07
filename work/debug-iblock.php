<?php
$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
$_SERVER['REQUEST_METHOD'] = 'GET';
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
define('BX_CRONTAB', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

CModule::IncludeModule('iblock');

echo "nlb_iblock_id('news') = ";
var_dump(function_exists('nlb_iblock_id') ? nlb_iblock_id('news') : 'NO_FUNC');

foreach ([
    ['CODE' => 'news', 'CHECK_PERMISSIONS' => 'N'],
    ['=CODE' => 'news', 'CHECK_PERMISSIONS' => 'N'],
    ['CODE' => 'news'],
] as $i => $filter) {
    $res = CIBlock::GetList(['SORT' => 'ASC'], $filter);
    $rows = [];
    while ($row = $res->Fetch()) {
        $rows[] = $row['ID'] . ':' . $row['CODE'] . ':' . $row['IBLOCK_TYPE_ID'];
    }
    echo "filter#$i " . json_encode($filter) . ' -> ' . (implode(', ', $rows) ?: '(empty)') . "\n";
}

echo 'CACHE_MANAGER=' . (isset($GLOBALS['CACHE_MANAGER']) ? 'yes' : 'no') . "\n";
echo 'CACHED_b_iblock=' . var_export(defined('CACHED_b_iblock'), true) . "\n";
