<?php
/**
 * Коллизия инфоблоков по CODE (P1-24).
 *
 * Зачем тест: `nlb_iblock_id()` искал инфоблок только по `CODE`. Код в
 * Битриксе не уникален — инфоблок `news` может лежать в чужом типе
 * (демо-контент другой редакции, чужой шаблон). Тогда миграция 002 считала
 * работу сделанной и пропускала создание, а сайт наполнялся не тем
 * инфоблоком. `db/check.php` этого не ловил: тип найденного инфоблока
 * никто не сверял.
 *
 * Тест работает на отдельном файле состояния: чужой инфоблок создаётся
 * до миграций, поэтому состояние прогона `db/tests/run.php` не трогается.
 *
 * Запуск: php db/tests/iblock-collision.php
 * Выход:  0 — свой инфоблок создан, конфликт виден, 1 — расхождение.
 */

declare(strict_types=1);

$root = getenv('NLB_ROOT');
$root = ($root === false || $root === '') ? dirname(__DIR__, 2) : (string)$root;
$stateFile = rtrim(str_replace('\\', '/', $root), '/') . '/state-collision.json';
putenv('NLB_STUB_STATE=' . $stateFile);
@unlink($stateFile);

require __DIR__ . '/../lib/boot.php';

$errors = [];

$type = new CIBlockType();
$typeId = $type->Add(['ID' => 'demo_content', 'SECTIONS' => 'Y']);
if ((string)$typeId !== 'demo_content') {
    $errors[] = 'не создан чужой тип demo_content: ' . (string)$type->LAST_ERROR;
}

$foreign = new CIBlock();
$foreignId = (int)$foreign->Add([
    'ACTIVE' => 'Y',
    'NAME' => 'Новости (демо-шаблон)',
    'CODE' => 'news',
    'IBLOCK_TYPE_ID' => 'demo_content',
]);
if ($foreignId <= 0) {
    $errors[] = 'чужой инфоблок news не создан: ' . (string)$foreign->LAST_ERROR;
}

$beforeCreate = nlb_iblock_id('news');
if ($beforeCreate !== 0) {
    $errors[] = 'nlb_iblock_id(news) принял чужой инфоблок ' . $beforeCreate . ' за свой';
}

$typesMigration = require __DIR__ . '/../migrations/001_iblock_types.php';
$iblocksMigration = require __DIR__ . '/../migrations/002_iblocks.php';
$typesMigration();
$result = $iblocksMigration();

$ownId = nlb_iblock_id('news');
if ($ownId <= 0) {
    $errors[] = 'миграция 002 не создала свой новостной инфоблок: код занят чужим';
} else {
    $ownType = (string)(NlbStubStore::$iblocks[$ownId]['IBLOCK_TYPE_ID'] ?? '');
    if ($ownType !== 'nlb_content') {
        $errors[] = 'свой news оказался в типе ' . $ownType . ' вместо nlb_content';
    }
    if ($ownId === $foreignId) {
        $errors[] = 'nlb_iblock_id(news) вернул чужой инфоблок ' . $foreignId;
    }
}

if ((int)($result['created'] ?? 0) < 1) {
    $errors[] = 'миграция 002 отчиталась о 0 созданных инфоблоках при занятом коде';
}

$foreignFound = nlb_iblock_foreign('news');
if (!isset($foreignFound[$foreignId])) {
    $errors[] = 'nlb_iblock_foreign(news) не увидел чужой инфоблок ' . $foreignId;
} elseif ((string)$foreignFound[$foreignId] !== 'demo_content') {
    $errors[] = 'nlb_iblock_foreign(news) вернул тип ' . $foreignFound[$foreignId]
        . ' вместо demo_content';
}

$again = $iblocksMigration();
if ((int)($again['created'] ?? -1) !== 0) {
    $errors[] = 'повторный запуск 002 создал ' . (int)$again['created'] . ' инфоблоков';
}

$newsCount = 0;
foreach (NlbStubStore::$iblocks as $iblock) {
    if ((string)($iblock['CODE'] ?? '') === 'news') {
        $newsCount++;
    }
}
if ($newsCount !== 2) {
    $errors[] = 'ожидалось два инфоблока news (свой и чужой), найдено ' . $newsCount;
}

$checkSource = (string)file_get_contents(__DIR__ . '/../check.php');
if (!str_contains($checkSource, 'nlb_iblock_foreign(')) {
    $errors[] = 'db/check.php не вызывает nlb_iblock_foreign() — конфликт не попадёт в отчёт';
}

$runtimeSource = (string)file_get_contents(
    dirname(__DIR__, 2) . '/local/php_interface/include/nlb_db.php'
);
if (!str_contains($runtimeSource, "IBLOCK_TYPE_ID") || !str_contains($runtimeSource, "'nlb_'")) {
    $errors[] = 'рантайм-копия nlb_iblock_id() не отсеивает инфоблоки чужих типов';
}

@unlink($stateFile);

if ($errors) {
    echo "Ошибки коллизии инфоблоков:\n";
    foreach ($errors as $error) {
        echo "  x {$error}\n";
    }
    exit(1);
}

echo 'OK: чужой news в другом типе не подменил свой, конфликт виден, повтор не дублирует' . "\n";
exit(0);
