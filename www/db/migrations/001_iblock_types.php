<?php
/**
 * 001 — типы инфоблоков.
 *
 * Создаёт типы nlb_content, nlb_catalog, nlb_services. Повторный запуск
 * ничего не дублирует: тип ищется по ID.
 */

return static function (): array {
    $config = nlb_load_config('iblocks');
    $created = 0;
    $skipped = 0;
    $notes = [];

    foreach ($config['types'] as $typeId => $type) {
        if (nlb_iblock_type_exists($typeId)) {
            $skipped++;
            $notes[] = "тип {$typeId} уже есть";
            continue;
        }

        $fields = [
            'ID' => $typeId,
            'SECTIONS' => $type['sections'],
            'IN_RSS' => $type['in_rss'],
            'SORT' => (int)$type['sort'],
            'LANG' => [],
        ];
        foreach (['ru', 'en'] as $lang) {
            $fields['LANG'][$lang] = [
                'NAME' => $type['lang'][$lang]['name'],
                'SECTION_NAME' => $type['lang'][$lang]['section_name'],
                'ELEMENT_NAME' => $type['lang'][$lang]['element_name'],
            ];
        }

        $object = new CIBlockType();
        if (!$object->Add($fields)) {
            $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
            throw new RuntimeException("тип {$typeId}: " . ($error ?: 'не удалось создать'));
        }

        $created++;
        $notes[] = "создан тип {$typeId}";
    }

    return ['created' => $created, 'skipped' => $skipped, 'notes' => $notes];
};
