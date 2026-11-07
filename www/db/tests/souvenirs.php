<?php
/**
 * Проверка миграции 010 на «книжной» базе.
 *
 * Запускается в песочнице после fixtures/legacy.php и db/run.php --only=009,010.
 * Смотрит, что книжные свойства, разделы и элементы заменены сувенирными,
 * печать дополнена до 37 позиций, услуги — до 8, а старые коды исчезли.
 */

declare(strict_types=1);

require __DIR__ . '/../lib/boot.php';

$errors = [];

function nlb_legacy_element(int $iblockId, string $xmlId): ?array
{
    $id = nlb_element_id($iblockId, $xmlId);
    if ($id <= 0) {
        return null;
    }
    $row = NlbStubStore::$elements[$id] ?? null;
    return $row ?: null;
}

function nlb_legacy_has_code(int $iblockId, string $code): bool
{
    foreach (NlbStubStore::$elements as $element) {
        if ((int)$element['IBLOCK_ID'] === $iblockId && (string)$element['CODE'] === $code) {
            return true;
        }
    }
    return false;
}

$booksId = nlb_iblock_id('books');
$collectionsId = nlb_iblock_id('collections');
$printingId = nlb_iblock_id('printing');
$servicesId = nlb_iblock_id('services');

/* 009: имя каталога и адрес списка. */
if ((string)(NlbStubStore::$iblocks[$booksId]['NAME'] ?? '') !== 'Сувенирная продукция') {
    $errors[] = '009: инфоблок books не переименован в «Сувенирная продукция»';
}
if ((string)(NlbStubStore::$iblocks[$booksId]['LIST_PAGE_URL'] ?? '') !== '/books/') {
    $errors[] = '009: LIST_PAGE_URL books = ' . (NlbStubStore::$iblocks[$booksId]['LIST_PAGE_URL'] ?? '');
}

/* 010: свойства книг. */
$author = nlb_property($booksId, 'AUTHOR');
$year = nlb_property($booksId, 'YEAR');
$price = nlb_property($booksId, 'PRICE');
if (!$author || (string)$author['NAME'] !== 'Материал') {
    $errors[] = '010: AUTHOR не переименован в «Материал»';
}
if (!$year || (string)$year['NAME'] !== 'Размер' || (string)$year['PROPERTY_TYPE'] !== 'S') {
    $errors[] = '010: YEAR должен быть строкой «Размер»';
}
if (!$price || (string)$price['PROPERTY_TYPE'] !== 'N') {
    $errors[] = '010: свойство PRICE не создано числом';
}

/* 010: раздел переименован, код сохранён. */
$fiction = nlb_section_id($booksId, 'fiction');
if ($fiction <= 0 || (string)(NlbStubStore::$sections[$fiction]['NAME'] ?? '') !== 'Брелоки') {
    $errors[] = '010: раздел fiction не переименован в «Брелоки»';
}

/* 010: книжный элемент стал сувениром. */
$book = nlb_legacy_element($booksId, 'nlb-book-001');
if (!$book) {
    $errors[] = '010: нет элемента nlb-book-001';
} else {
    if ((string)$book['NAME'] !== 'Брелок «Книга»') {
        $errors[] = '010: название nlb-book-001 = ' . $book['NAME'];
    }
    if ((string)$book['CODE'] !== 'brelok-kniga') {
        $errors[] = '010: код nlb-book-001 = ' . $book['CODE'];
    }
    if (str_contains((string)$book['DETAIL_TEXT'], 'Книжный текст')) {
        $errors[] = '010: у nlb-book-001 остался книжный текст';
    }
    if ((int)$book['PREVIEW_PICTURE'] !== 0) {
        $errors[] = '010: у сувенира осталась книжная обложка';
    }
    $values = $book['PROPERTY_VALUES'] ?? [];
    if ((string)($values['AUTHOR'] ?? '') !== 'Латунь') {
        $errors[] = '010: AUTHOR сувенира = ' . var_export($values['AUTHOR'] ?? null, true);
    }
    if ((string)($values['YEAR'] ?? '') !== '4 × 2,5 см') {
        $errors[] = '010: YEAR сувенира = ' . var_export($values['YEAR'] ?? null, true);
    }
    if ((int)($values['PRICE'] ?? 0) !== 350) {
        $errors[] = '010: PRICE сувенира = ' . var_export($values['PRICE'] ?? null, true);
    }
    if (isset($values['AGE_LIMIT'])) {
        $errors[] = '010: у сувенира осталось книжное свойство AGE_LIMIT';
    }
}
if (nlb_legacy_has_code($booksId, 'kniga-1')) {
    $errors[] = '010: старый код kniga-1 остался в books';
}

/* 010: коллекции. */
$collection = nlb_legacy_element($collectionsId, 'nlb-col-001');
if (!$collection || (string)$collection['NAME'] !== 'Набор для коллег') {
    $errors[] = '010: коллекция nlb-col-001 не заменена на подарочный набор';
}

/* 010: печать — 37 позиций и новый UNIT. */
if (nlb_iblock_count('printing') !== 37) {
    $errors[] = '010: печатных услуг ' . nlb_iblock_count('printing') . ' вместо 37';
}
$print = nlb_legacy_element($printingId, 'nlb-prn-001');
if (!$print) {
    $errors[] = '010: нет элемента nlb-prn-001';
} else {
    if ((string)$print['CODE'] !== 'pechat-dokumentov') {
        $errors[] = '010: код nlb-prn-001 = ' . $print['CODE'];
    }
    $values = $print['PROPERTY_VALUES'] ?? [];
    if ((string)($values['UNIT'] ?? '') !== 'за страницу') {
        $errors[] = '010: UNIT печати = ' . var_export($values['UNIT'] ?? null, true);
    }
}
if (!nlb_legacy_element($printingId, 'nlb-prn-037')) {
    $errors[] = '010: не досоздана печать nlb-prn-037';
}

/* 010: услуги — 8 позиций, новые свойства заполнены. */
if (nlb_iblock_count('services') !== 8) {
    $errors[] = '010: услуг ' . nlb_iblock_count('services') . ' вместо 8';
}
$service = nlb_legacy_element($servicesId, 'nlb-srv-001');
if (!$service) {
    $errors[] = '010: нет элемента nlb-srv-001';
} else {
    if ((string)$service['CODE'] !== 'observation-deck') {
        $errors[] = '010: код nlb-srv-001 = ' . $service['CODE'];
    }
    $values = $service['PROPERTY_VALUES'] ?? [];
    if ((string)($values['SUBTITLE'] ?? '') !== 'Панорамный вид на город') {
        $errors[] = '010: SUBTITLE услуги = ' . var_export($values['SUBTITLE'] ?? null, true);
    }
    if (count((array)($values['TARIFFS'] ?? [])) !== 3) {
        $errors[] = '010: у услуги не 3 тарифа';
    }
    if ((string)($values['PHONE'] ?? '') === '') {
        $errors[] = '010: у услуги не заполнен телефон';
    }
}
if (!nlb_property($servicesId, 'SUBTITLE')) {
    $errors[] = '010: свойство SUBTITLE не создано';
}
if (nlb_legacy_has_code($servicesId, 'staraya-usluga-1')) {
    $errors[] = '010: старый код услуги остался';
}

/* Никаких дублей и потерь. */
$seen = [];
foreach (NlbStubStore::$elements as $element) {
    $xmlId = (string)$element['XML_ID'];
    if (isset($seen[$xmlId])) {
        $errors[] = '010: дубль XML_ID ' . $xmlId;
    }
    $seen[$xmlId] = true;
}
if (count(NlbStubStore::$elements) !== 91) {
    $errors[] = '010: элементов ' . count(NlbStubStore::$elements) . ' вместо 91';
}
if (count(NlbStubStore::$sections) !== 12) {
    $errors[] = '010: разделов ' . count(NlbStubStore::$sections) . ' вместо 12';
}

if ($errors) {
    echo "Ошибки проверки 010:\n";
    foreach ($errors as $error) {
        echo "  x {$error}\n";
    }
    exit(1);
}

echo "OK: книжная база переведена на сувениры\n";
exit(0);
