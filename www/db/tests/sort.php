<?php
/**
 * Порядок карточек: у каждого демо-элемента свой sort (P1-26).
 *
 * Зачем тест: миграция 005 пишет SORT из `$item['sort'] ?? 500`, а в
 * db/config/data/*.php ключа sort не было — в базе у всех элементов
 * стояло 500, и порядок карточек выбирала СУБД (на стенде каталог печати
 * начинался с последнего элемента конфига). Проверяем три вещи:
 * sort задан и уникален в конфиге, значения идут по порядку конфига,
 * и миграция записала именно их, а не 500.
 *
 * Запускается из db/tests/run.php в песочнице с NLB_ROOT и заглушками ядра.
 *
 * Запуск: php db/tests/sort.php
 * Выход:  0 — порядок задан конфигом, 1 — расхождение.
 */

declare(strict_types=1);

require __DIR__ . '/../lib/boot.php';

$errors = [];
$total = 0;
$data = nlb_load_data();

foreach ($data as $iblockCode => $file) {
    $sorts = [];
    $previous = 0;

    foreach ($file['items'] as $index => $item) {
        $xmlId = (string)($item['xml_id'] ?? '');
        $label = $iblockCode . '[' . ($index + 1) . '] ' . $xmlId;
        $sort = $item['sort'] ?? null;

        if (!is_int($sort) || $sort <= 0) {
            $errors[] = $label . ': sort должен быть положительным числом';
            continue;
        }
        if (isset($sorts[$sort])) {
            $errors[] = $label . ': sort ' . $sort . ' уже занят элементом ' . $sorts[$sort];
        }
        $sorts[$sort] = $xmlId;
        if ($sort <= $previous) {
            $errors[] = $label . ': sort ' . $sort . ' не больше предыдущего ' . $previous;
        }
        $previous = $sort;
    }

    $iblockId = nlb_iblock_id($iblockCode);
    if ($iblockId <= 0) {
        $errors[] = $iblockCode . ': инфоблок не найден';
        continue;
    }

    foreach ($file['items'] as $item) {
        $xmlId = (string)($item['xml_id'] ?? '');
        $total++;
        $elementId = nlb_element_id($iblockId, $xmlId);
        if ($elementId <= 0) {
            $errors[] = $iblockCode . '.' . $xmlId . ': элемент не создан';
            continue;
        }
        $stored = (int)(NlbStubStore::$elements[$elementId]['SORT'] ?? 0);
        if ($stored !== (int)($item['sort'] ?? 0)) {
            $errors[] = $iblockCode . '.' . $xmlId . ': SORT в базе ' . $stored
                . ' вместо ' . (int)($item['sort'] ?? 0);
        }
    }
}

if ($errors) {
    echo "Ошибки sort:\n";
    foreach ($errors as $error) {
        echo "  x {$error}\n";
    }
    exit(1);
}

echo 'OK: у ' . $total . ' демо-элементов свой sort, порядок совпадает с конфигом' . "\n";
exit(0);
