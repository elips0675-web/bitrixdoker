<?php
/**
 * 005 — демо-контент.
 *
 * Проходит по db/config/data/*.php и создаёт элементы, которых ещё нет
 * (поиск по XML_ID). Повторный запуск дублей не создаёт.
 *
 * Даты из конфига — ISO; в базу пишутся в формате сайта.
 * Картинки берутся из assets шаблона и копируются в upload.
 */

return static function (): array {
    $config = nlb_load_config('iblocks');
    $data = nlb_load_data();
    $created = 0;
    $skipped = 0;
    $notes = [];

    foreach ($data as $code => $payload) {
        if (!isset($config['iblocks'][$code])) {
            throw new RuntimeException("данные {$code}: инфоблок не объявлен в config/iblocks.php");
        }

        $iblock = $config['iblocks'][$code];
        $iblockId = nlb_iblock_id($code);
        if ($iblockId <= 0) {
            throw new RuntimeException("данные {$code}: инфоблок не найден (сначала 002)");
        }

        foreach ($payload['items'] as $item) {
            $xmlId = (string)$item['xml_id'];
            if (nlb_element_id($iblockId, $xmlId) > 0) {
                $skipped++;
                continue;
            }

            /* У событий дата начала хранится и в свойстве DATE, и в
               ACTIVE_FROM: только по ACTIVE_FROM сортировка и фильтр
               «предстоящие» работают правильно (строка свойства в формате
               сайта сравнивается как текст). Компоненты афиши подключаются
               с CHECK_DATES => 'N', поэтому будущие события не скрываются. */
            $activeFrom = (string)$item['active_from'];
            $eventDate = (string)($item['properties']['DATE'] ?? '');
            if ($code === 'events' && $eventDate !== '') {
                $activeFrom = $eventDate;
            }

            $fields = [
                'IBLOCK_ID' => $iblockId,
                'NAME' => $item['name'],
                'CODE' => (string)$item['code'],
                'XML_ID' => $xmlId,
                'ACTIVE' => 'Y',
                'DATE_ACTIVE_FROM' => nlb_iso_to_site($activeFrom),
                'PREVIEW_TEXT' => (string)($item['preview_text'] ?? ''),
                'PREVIEW_TEXT_TYPE' => 'text',
                'DETAIL_TEXT' => (string)($item['detail_text'] ?? ''),
                'DETAIL_TEXT_TYPE' => 'html',
                'SORT' => (int)($item['sort'] ?? 500),
                'PROPERTY_VALUES' => nlb_prepare_property_values(
                    $iblock,
                    is_array($item['properties'] ?? null) ? $item['properties'] : [],
                    $iblockId
                ),
            ];

            $sectionCode = (string)($item['section'] ?? '');
            if ($sectionCode !== '') {
                $sectionId = nlb_section_id($iblockId, $sectionCode);
                if ($sectionId <= 0) {
                    throw new RuntimeException("элемент {$xmlId}: раздел {$sectionCode} не найден");
                }
                $fields['IBLOCK_SECTION_ID'] = $sectionId;
            }

            $picture = nlb_file_array($item['image'] ?? null);
            if ($picture) {
                $fields['PREVIEW_PICTURE'] = $picture;
                $fields['DETAIL_PICTURE'] = $picture;
            }

            $object = new CIBlockElement();
            $id = (int)$object->Add($fields);
            if ($id <= 0) {
                $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
                throw new RuntimeException("элемент {$xmlId}: " . ($error ?: 'не удалось создать'));
            }

            $created++;
            $notes[] = "создан элемент {$xmlId} (ID {$id})";
        }
    }

    return ['created' => $created, 'skipped' => $skipped, 'notes' => $notes];
};
