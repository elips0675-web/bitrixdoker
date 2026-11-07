<?php
/**
 * Фикстура «книжная база» для проверки миграции 010.
 *
 * Запускается в песочнице после полного прогона миграций. Возвращает
 * книги, коллекции, печать и услуги к состоянию, которое было до перехода
 * на сувениры: старые названия свойств, тип YEAR = N, книжные элементы,
 * 8 печатных услуг и 6 услуг. Так проверяется именно обновление
 * существующей установки, а не создание с нуля.
 */

declare(strict_types=1);

require __DIR__ . '/../lib/boot.php';

$booksId = nlb_iblock_id('books');
$collectionsId = nlb_iblock_id('collections');
$printingId = nlb_iblock_id('printing');
$servicesId = nlb_iblock_id('services');

foreach ([$booksId, $collectionsId, $printingId, $servicesId] as $iblockId) {
    if ($iblockId <= 0) {
        fwrite(STDERR, "фикстура: инфоблок не найден\n");
        exit(1);
    }
}

/* 009: книжное имя и старый адрес каталога. */
NlbStubStore::$iblocks[$booksId]['NAME'] = 'Книги';
NlbStubStore::$iblocks[$booksId]['LIST_PAGE_URL'] = '/catalog/';

/* Свойства книг: старые названия и числовой YEAR. */
$bookPropertyNames = [
    'AUTHOR' => 'Автор',
    'YEAR' => 'Год издания',
    'ISBN' => 'ISBN',
    'PUBLISHER' => 'Издательство',
    'PAGES' => 'Страниц',
    'SHELF' => 'Полка',
];
foreach (NlbStubStore::$properties as $propertyId => $property) {
    if ((int)$property['IBLOCK_ID'] !== $booksId) {
        continue;
    }
    $code = (string)$property['CODE'];
    if (isset($bookPropertyNames[$code])) {
        NlbStubStore::$properties[$propertyId]['NAME'] = $bookPropertyNames[$code];
    }
    if ($code === 'YEAR') {
        NlbStubStore::$properties[$propertyId]['PROPERTY_TYPE'] = 'N';
    }
    if ($code === 'PRICE') {
        unset(NlbStubStore::$properties[$propertyId]);
    }
}

/* Разделы книг: прежние названия. */
$bookSectionNames = [
    'fiction' => 'Художественная литература',
    'history' => 'История',
    'science' => 'Наука',
    'children' => 'Детская литература',
    'business' => 'Деловая литература',
    'art' => 'Искусство',
];
foreach (NlbStubStore::$sections as $sectionId => $section) {
    if ((int)$section['IBLOCK_ID'] === $booksId && isset($bookSectionNames[$section['CODE']])) {
        NlbStubStore::$sections[$sectionId]['NAME'] = $bookSectionNames[$section['CODE']];
    }
}

$availableEnum = nlb_enum_id($booksId, 'AVAILABLE', 'available');
$ageEnum = nlb_enum_id($booksId, 'AGE_LIMIT', 'all');

/* 24 книжных элемента с теми же XML_ID, что у сувениров. */
for ($i = 1; $i <= 24; $i++) {
    $xmlId = sprintf('nlb-book-%03d', $i);
    $elementId = nlb_element_id($booksId, $xmlId);
    if ($elementId <= 0) {
        fwrite(STDERR, "фикстура: нет элемента {$xmlId}\n");
        exit(1);
    }
    NlbStubStore::$elements[$elementId]['NAME'] = 'Книга ' . $i;
    NlbStubStore::$elements[$elementId]['CODE'] = 'kniga-' . $i;
    NlbStubStore::$elements[$elementId]['PREVIEW_TEXT'] = 'Книжное описание ' . $i;
    NlbStubStore::$elements[$elementId]['DETAIL_TEXT'] = '<p>Книжный текст ' . $i . '</p>';
    NlbStubStore::$elements[$elementId]['PREVIEW_PICTURE'] = 1;
    NlbStubStore::$elements[$elementId]['DETAIL_PICTURE'] = 1;
    NlbStubStore::$elements[$elementId]['PROPERTY_VALUES'] = [
        'AUTHOR' => 'Автор ' . $i,
        'YEAR' => '2020',
        'ISBN' => '978-5-0000-' . sprintf('%04d', $i),
        'PUBLISHER' => 'Издательство',
        'PAGES' => 200 + $i,
        'AVAILABLE' => $availableEnum,
        'SHELF' => 'Полка ' . $i,
        'AGE_LIMIT' => $ageEnum,
    ];
}

/* Коллекции: книжные подборки. */
for ($i = 1; $i <= 6; $i++) {
    $xmlId = sprintf('nlb-col-%03d', $i);
    $elementId = nlb_element_id($collectionsId, $xmlId);
    if ($elementId <= 0) {
        fwrite(STDERR, "фикстура: нет элемента {$xmlId}\n");
        exit(1);
    }
    NlbStubStore::$elements[$elementId]['NAME'] = 'Книжная коллекция ' . $i;
    NlbStubStore::$elements[$elementId]['CODE'] = 'knizhnaya-kollekciya-' . $i;
    NlbStubStore::$elements[$elementId]['PREVIEW_TEXT'] = 'Подборка книг ' . $i;
    NlbStubStore::$elements[$elementId]['DETAIL_TEXT'] = '<p>Книжный набор ' . $i . '</p>';
}

/* Печать: было 8 позиций со старым UNIT. */
for ($i = 9; $i <= 37; $i++) {
    $elementId = nlb_element_id($printingId, sprintf('nlb-prn-%03d', $i));
    if ($elementId > 0) {
        unset(NlbStubStore::$elements[$elementId]);
    }
}
for ($i = 1; $i <= 8; $i++) {
    $elementId = nlb_element_id($printingId, sprintf('nlb-prn-%03d', $i));
    if ($elementId <= 0) {
        fwrite(STDERR, "фикстура: нет печати {$i}\n");
        exit(1);
    }
    NlbStubStore::$elements[$elementId]['NAME'] = 'Старая печать ' . $i;
    NlbStubStore::$elements[$elementId]['CODE'] = 'staraya-pechat-' . $i;
    NlbStubStore::$elements[$elementId]['PREVIEW_TEXT'] = 'Старое описание печати ' . $i;
    $values = NlbStubStore::$elements[$elementId]['PROPERTY_VALUES'] ?? [];
    $values['UNIT'] = '₽ / страница';
    NlbStubStore::$elements[$elementId]['PROPERTY_VALUES'] = $values;
}

/* Услуги: было 6 старых, новых свойств ещё нет. */
for ($i = 7; $i <= 8; $i++) {
    $elementId = nlb_element_id($servicesId, sprintf('nlb-srv-%03d', $i));
    if ($elementId > 0) {
        unset(NlbStubStore::$elements[$elementId]);
    }
}
for ($i = 1; $i <= 6; $i++) {
    $elementId = nlb_element_id($servicesId, sprintf('nlb-srv-%03d', $i));
    if ($elementId <= 0) {
        fwrite(STDERR, "фикстура: нет услуги {$i}\n");
        exit(1);
    }
    NlbStubStore::$elements[$elementId]['NAME'] = 'Старая услуга ' . $i;
    NlbStubStore::$elements[$elementId]['CODE'] = 'staraya-usluga-' . $i;
    NlbStubStore::$elements[$elementId]['PREVIEW_TEXT'] = 'Старое описание услуги ' . $i;
    NlbStubStore::$elements[$elementId]['DETAIL_TEXT'] = '<p>Старый текст услуги ' . $i . '</p>';
}
for ($i = 1; $i <= 6; $i++) {
    $elementId = nlb_element_id($servicesId, sprintf('nlb-srv-%03d', $i));
    if ($elementId <= 0) {
        continue;
    }
    $values = NlbStubStore::$elements[$elementId]['PROPERTY_VALUES'] ?? [];
    unset($values['SUBTITLE'], $values['PRICE_LABEL'], $values['SCHEDULE'], $values['PHONE'], $values['EMAIL'], $values['TARIFFS']);
    NlbStubStore::$elements[$elementId]['PROPERTY_VALUES'] = $values;
}
$newServiceProperties = ['SUBTITLE', 'PRICE_LABEL', 'SCHEDULE', 'PHONE', 'EMAIL', 'TARIFFS'];
foreach (NlbStubStore::$properties as $propertyId => $property) {
    if ((int)$property['IBLOCK_ID'] === $servicesId && in_array($property['CODE'], $newServiceProperties, true)) {
        unset(NlbStubStore::$properties[$propertyId]);
    }
}

echo "OK: книжная база собрана\n";
exit(0);
