<?php
/**
 * 009 — названия и адреса существующих инфоблоков.
 *
 * 002 создаёт инфоблоки с актуальными полями, но существующую установку
 * пропускает. Из-за этого каталог сувениров в админке мог остаться
 * «Книгами», а адреса списков — старыми. Шаг сверяет метаданные
 * шести контентных инфоблоков с db/config/iblocks.php и обновляет
 * только расхождения. Повторный запуск ничего не меняет.
 *
 * Свойства, разделы и элементы синхронизирует следующий шаг 010.
 */

return static function (): array {
    $config = nlb_load_config('iblocks');
    $updated = 0;
    $skipped = 0;
    $notes = [];

    $codes = ['news', 'events', 'collections', 'books', 'services', 'printing'];

    foreach ($codes as $code) {
        if (!isset($config['iblocks'][$code])) {
            throw new RuntimeException("009: инфоблок {$code} не объявлен в config/iblocks.php");
        }

        $iblock = $config['iblocks'][$code];
        $iblockId = nlb_iblock_id($code);
        if ($iblockId <= 0) {
            throw new RuntimeException("009: инфоблок {$code} не найден (сначала 002)");
        }

        $res = CIBlock::GetList([], [
            'ID' => $iblockId,
            'CHECK_PERMISSIONS' => 'N',
        ]);
        $current = $res->Fetch();
        if (!$current) {
            throw new RuntimeException("009: инфоблок {$code} (ID {$iblockId}) не прочитан");
        }

        $fields = [
            'NAME' => (string)$iblock['name'],
            'SORT' => (int)$iblock['sort'],
            'LIST_PAGE_URL' => (string)$iblock['list_page_url'],
            'DETAIL_PAGE_URL' => (string)$iblock['detail_page_url'],
            'SECTION_PAGE_URL' => (string)$iblock['section_page_url'],
            'INDEX_ELEMENT' => (string)($iblock['index'] ?? 'Y'),
            'INDEX_SECTION' => (string)$iblock['sections'],
        ];

        $diff = [];
        foreach (['NAME', 'SORT', 'LIST_PAGE_URL', 'DETAIL_PAGE_URL', 'SECTION_PAGE_URL', 'INDEX_ELEMENT', 'INDEX_SECTION'] as $field) {
            $currentValue = $field === 'SORT'
                ? (int)($current[$field] ?? 0)
                : (string)($current[$field] ?? '');
            $targetValue = $field === 'SORT'
                ? (int)$fields[$field]
                : (string)$fields[$field];
            if ($currentValue !== $targetValue) {
                $diff[$field] = $fields[$field];
            }
        }

        if (!$diff) {
            $skipped++;
            continue;
        }

        $object = new CIBlock();
        if (!$object->Update($iblockId, $diff)) {
            $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
            throw new RuntimeException("009: инфоблок {$code}: " . ($error ?: 'не обновлён'));
        }

        $updated++;
        $notes[] = "обновлён инфоблок {$code}: " . implode(', ', array_keys($diff));
    }

    return [
        'created' => 0,
        'updated' => $updated,
        'skipped' => $skipped,
        'notes' => $notes,
    ];
};
