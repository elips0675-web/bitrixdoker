<?php
/**
 * Проверка значений, которые миграция записала в «базу» (заглушки).
 *
 * Запускается из db/tests/run.php в песочнице с флагом NLB_ROOT.
 */

declare(strict_types=1);

require __DIR__ . '/../lib/boot.php';

$errors = [];

$eventsIblock = nlb_iblock_id('events');
$eventId = nlb_element_id($eventsIblock, 'nlb-evt-001');
if ($eventId <= 0) {
    $errors[] = 'не найден элемент nlb-evt-001';
} else {
    $values = NlbStubStore::$elements[$eventId]['PROPERTY_VALUES'] ?? [];
    if (($values['DATE'] ?? '') !== '06.11.2026 18:30:00') {
        $errors[] = 'DATE события: ' . var_export($values['DATE'] ?? null, true)
            . ' вместо 06.11.2026 18:30:00';
    }
    if (($values['CATEGORY'] ?? 0) <= 0) {
        $errors[] = 'CATEGORY события не преобразован в ID значения списка';
    }
    if (!isset($values['SEATS']) || $values['SEATS'] !== 40) {
        $errors[] = 'SEATS события должен быть числом 40';
    }
}

$newsIblock = nlb_iblock_id('news');
$newsId = nlb_element_id($newsIblock, 'nlb-news-001');
if ($newsId <= 0) {
    $errors[] = 'не найден элемент nlb-news-001';
} else {
    $values = NlbStubStore::$elements[$newsId]['PROPERTY_VALUES'] ?? [];
    $tags = $values['TAGS'] ?? [];
    if (!is_array($tags) || count($tags) !== 2) {
        $errors[] = 'TAGS новости: ожидались 2 ID значений, получено ' . var_export($tags, true);
    } else {
        foreach ($tags as $tagId) {
            if (!isset(NlbStubStore::$enums[$tagId])) {
                $errors[] = 'TAGS новости: ID ' . $tagId . ' не найден среди значений списка';
            }
        }
    }
    if ((int)(NlbStubStore::$elements[$newsId]['PREVIEW_PICTURE'] ?? 0) <= 0) {
        $errors[] = 'у новости не заполнена картинка анонса';
    }
    if (empty(NlbStubStore::$elements[$newsId]['DATE_ACTIVE_FROM'])) {
        $errors[] = 'у новости не заполнена дата активности';
    }
}

$booksIblock = nlb_iblock_id('books');
$bookId = nlb_element_id($booksIblock, 'nlb-book-001');
if ($bookId <= 0) {
    $errors[] = 'не найден элемент nlb-book-001';
} else {
    $values = NlbStubStore::$elements[$bookId]['PROPERTY_VALUES'] ?? [];
    if (($values['YEAR'] ?? '') !== '4 × 2,5 см') {
        $errors[] = 'YEAR сувенира: ' . var_export($values['YEAR'] ?? null, true)
            . ' вместо 4 × 2,5 см';
    }
    if (($values['PRICE'] ?? 0) !== 350) {
        $errors[] = 'PRICE сувенира должен быть числом 350';
    }
    if (($values['AVAILABLE'] ?? 0) <= 0) {
        $errors[] = 'AVAILABLE сувенира не преобразован в ID значения списка';
    }
}

$elements = count(NlbStubStore::$elements);
if ($elements !== 91) {
    $errors[] = 'элементов в песочнице ' . $elements . ' вместо 91';
}

$sections = count(NlbStubStore::$sections);
if ($sections !== 12) {
    $errors[] = 'разделов в песочнице ' . $sections . ' вместо 12';
}

/* Счётчиков в структуре нет: количество берётся из инфоблока. */
if (nlb_iblock_count('news') !== 10) {
    $errors[] = 'nlb_iblock_count(news) = ' . nlb_iblock_count('news') . ' вместо 10';
}
if (nlb_iblock_count('printing') !== 37) {
    $errors[] = 'nlb_iblock_count(printing) = ' . nlb_iblock_count('printing') . ' вместо 37';
}
$counts = nlb_counts();
if (array_sum($counts) !== 91) {
    $errors[] = 'сумма nlb_counts() = ' . array_sum($counts) . ' вместо 91';
}

/*
 * Поиск после установки в базу: документы берутся из инфоблоков, а не из
 * db/config/data/*.php. Свойства (автор, теги, цена) обязаны попадать в
 * текст документа — иначе на живой базе поиск хуже демо.
 */
require_once dirname(__DIR__, 2) . '/local/php_interface/include/nlb_search.php';

$searchDocuments = nlb_search_documents_from_bitrix(nlb_root());
if (count($searchDocuments) !== 91) {
    $errors[] = 'поиск: документов из инфоблоков ' . count($searchDocuments) . ' вместо 91';
}

$searchById = [];
foreach ($searchDocuments as $searchDocument) {
    $searchById[(string)($searchDocument['id'] ?? '')] = $searchDocument;
}

$newsDocument = $searchById['news:' . $newsId] ?? null;
if (!is_array($newsDocument)) {
    $errors[] = 'поиск: нет документа news:' . $newsId;
} else {
    $newsText = (string)($newsDocument['text'] ?? '');
    if (mb_strpos($newsText, 'Пресс-служба') === false) {
        $errors[] = 'поиск: в тексте новости нет свойства AUTHOR';
    }
    if (mb_strpos($newsText, 'Цифровые ресурсы') === false) {
        $errors[] = 'поиск: в тексте новости нет текста значения списка TAGS';
    }
}

$bookDocument = $searchById['books:' . $bookId] ?? null;
if (!is_array($bookDocument)) {
    $errors[] = 'поиск: нет документа books:' . $bookId;
} else {
    $bookText = (string)($bookDocument['text'] ?? '');
    if (mb_strpos($bookText, 'Латунь') === false) {
        $errors[] = 'поиск: в тексте сувенира нет свойства AUTHOR';
    }
    if (mb_strpos($bookText, '350') === false) {
        $errors[] = 'поиск: в тексте сувенира нет числа из свойства PRICE';
    }
}

/* 002: у инфоблоков форм CODE и XML_ID не обязательны — иначе добавление
   падает на проверке обязательных полей раньше OnBeforeIBlockElementAdd. */
foreach (['requests', 'bookings', 'orders'] as $formCode) {
    $formIblockId = nlb_iblock_id($formCode);
    $formFields = NlbStubStore::$iblocks[$formIblockId]['FIELDS'] ?? [];
    foreach (['CODE', 'XML_ID'] as $fieldCode) {
        if ((string)($formFields[$fieldCode]['IS_REQUIRED'] ?? '') !== 'N') {
            $errors[] = 'инфоблок ' . $formCode . ': поле ' . $fieldCode . ' осталось обязательным';
        }
    }
}
$newsFields = NlbStubStore::$iblocks[$newsIblock]['FIELDS'] ?? [];
if ((string)($newsFields['CODE']['IS_REQUIRED'] ?? '') !== 'Y') {
    $errors[] = 'новости: CODE должен остаться обязательным';
}

if ($errors) {
    echo "Ошибки inspect:\n";
    foreach ($errors as $error) {
        echo "  x {$error}\n";
    }
    exit(1);
}

echo "OK: значения свойств, даты, списки и картинки корректны; свойства попадают в документы поиска\n";
exit(0);
