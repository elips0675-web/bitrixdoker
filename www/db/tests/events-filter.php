<?php
/**
 * Афиша: фильтр «предстоящие / прошедшие» по DATE_ACTIVE_FROM (P1-27).
 *
 * Зачем тест: страница «Прошедшие» собирала фильтр ключом `<ACTIVE_FROM`,
 * а ядро такой ключ молча игнорирует — список показывал те же будущие
 * события. Ключ диапазона дат один: DATE_ACTIVE_FROM. Проверяем сам
 * хелпер nlb_events_filter() и то, что старый ключ не фильтрует.
 *
 * Запускается из db/tests/run.php в песочнице с NLB_ROOT и заглушками ядра.
 *
 * Запуск: php db/tests/events-filter.php
 * Выход:  0 — фильтр делит события по дате, 1 — расхождение.
 */

declare(strict_types=1);

require __DIR__ . '/../lib/boot.php';
require_once nlb_root() . '/local/php_interface/include/nlb_db.php';

$errors = [];

if (!function_exists('nlb_events_filter')) {
    fwrite(STDERR, "events-filter.php: нет функции nlb_events_filter()\n");
    exit(2);
}

$now = '01.01.2025 00:00:00';
$past = nlb_events_filter(true, $now);
$future = nlb_events_filter(false, $now);

if (array_keys($past) !== ['<DATE_ACTIVE_FROM']) {
    $errors[] = 'фильтр «Прошедшие»: ожидался ключ <DATE_ACTIVE_FROM, получено '
        . implode(', ', array_keys($past));
}
if (array_keys($future) !== ['>=DATE_ACTIVE_FROM']) {
    $errors[] = 'фильтр «Предстоящие»: ожидался ключ >=DATE_ACTIVE_FROM, получено '
        . implode(', ', array_keys($future));
}
if ((string)($past['<DATE_ACTIVE_FROM'] ?? '') !== $now
    || (string)($future['>=DATE_ACTIVE_FROM'] ?? '') !== $now) {
    $errors[] = 'фильтр не сохранил переданное «сейчас»';
}

$iblockId = nlb_iblock_id('events');
if ($iblockId <= 0) {
    $errors[] = 'инфоблок events не найден';
}

/* Два пробных элемента: до и после «сейчас». Настоящие элементы афиши
   в песочнице тоже есть, поэтому считаем только свои. */
$elements = NlbStubStore::$elements;
NlbStubStore::$elements[900001] = [
    'ID' => 900001,
    'IBLOCK_ID' => $iblockId,
    'XML_ID' => 'nlb-p127-past',
    'NAME' => 'Прошедшее событие',
    'ACTIVE' => 'Y',
    'DATE_ACTIVE_FROM' => '01.01.2020 10:00:00',
    'SORT' => 900,
];
NlbStubStore::$elements[900002] = [
    'ID' => 900002,
    'IBLOCK_ID' => $iblockId,
    'XML_ID' => 'nlb-p127-future',
    'NAME' => 'Будущее событие',
    'ACTIVE' => 'Y',
    'DATE_ACTIVE_FROM' => '01.01.2030 10:00:00',
    'SORT' => 910,
];

$select = static function (array $filter) use ($iblockId): array {
    $res = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        $filter + ['IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'XML_ID', 'DATE_ACTIVE_FROM']
    );
    $xmlIds = [];
    while ($row = $res->Fetch()) {
        $xmlId = (string)($row['XML_ID'] ?? '');
        if (str_starts_with($xmlId, 'nlb-p127-')) {
            $xmlIds[] = $xmlId;
        }
    }
    return $xmlIds;
};

$pastIds = $select($past);
$futureIds = $select($future);
$oldPastIds = $select(['<ACTIVE_FROM' => $now]);
$oldFutureIds = $select(['>=ACTIVE_FROM' => $now]);

NlbStubStore::$elements = $elements;

if ($pastIds !== ['nlb-p127-past']) {
    $errors[] = '«Прошедшие»: ожидалось [nlb-p127-past], получено [' . implode(', ', $pastIds) . ']';
}
if ($futureIds !== ['nlb-p127-future']) {
    $errors[] = '«Предстоящие»: ожидалось [nlb-p127-future], получено [' . implode(', ', $futureIds) . ']';
}
if (count($oldPastIds) !== 2 || count($oldFutureIds) !== 2) {
    $errors[] = 'ключ ACTIVE_FROM в заглушке перестал игнорироваться: '
        . '«Прошедшие» [' . implode(', ', $oldPastIds) . '], '
        . '«Предстоящие» [' . implode(', ', $oldFutureIds) . ']';
}

if ($errors) {
    echo "Ошибки events-filter:\n";
    foreach ($errors as $error) {
        echo "  x {$error}\n";
    }
    exit(1);
}

echo "OK: «Прошедшие» и «Предстоящие» делят события по DATE_ACTIVE_FROM\n";
exit(0);
