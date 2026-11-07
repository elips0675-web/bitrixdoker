<?php
/**
 * 010 — перевод существующей книжной базы на сувениры.
 *
 * Свежая установка получает актуальный контент из db/config/ (шаги 003–005).
 * Уже созданная база иначе осталась бы книжной: 003 и 005 пропускают
 * существующие свойства и элементы. Этот шаг приводит только свои объекты
 * (nlb-*) к текущему конфигу:
 *
 *  - свойства books/collections/printing/services: название, тип, кратность,
 *    сортировка и значения списков;
 *  - разделы books: название и сортировка (коды не меняются, адреса живут);
 *  - элементы: название, код, текст, дата, раздел, картинка и все свойства;
 *  - элементы с XML_ID nlb-*, которых больше нет в конфиге, удаляются.
 *
 * Повторный запуск ничего не меняет: каждое поле сравнивается с текущим,
 * а не перезаписывается вслепую. Чужие элементы и разделы не затрагиваются.
 */

return static function (): array {
    $config = nlb_load_config('iblocks');
    $data = nlb_load_data();

    $created = 0;
    $updated = 0;
    $skipped = 0;
    $notes = [];

    /* Только контентные инфоблоки. Заявки (requests/bookings/orders)
       наполняются с сайта и миграцией не переписываются. */
    $contentCodes = ['books', 'collections', 'printing', 'services'];

    /**
     * Значения свойства в едином виде: массив непустых строк.
     * Списки хранят ID значений, числа приходят как int — сравниваем строки.
     */
    $normalize = static function ($value): array {
        $values = is_array($value) ? $value : [$value];
        $result = [];
        foreach ($values as $one) {
            if (is_array($one)) {
                $one = $one['VALUE'] ?? '';
            }
            if (!is_scalar($one)) {
                continue;
            }
            $one = trim((string)$one);
            if ($one !== '') {
                $result[] = $one;
            }
        }
        sort($result, SORT_STRING);
        return $result;
    };

    /**
     * Текущие значения свойств элемента: CODE => массив строк.
     * Для списков Bitrix возвращает ID значения в VALUE.
     */
    $elementValues = static function (int $iblockId, int $elementId): array {
        $result = [];
        $res = CIBlockElement::GetProperty($iblockId, $elementId, ['sort' => 'asc'], []);
        while ($row = $res->Fetch()) {
            $code = (string)($row['CODE'] ?? '');
            if ($code === '') {
                continue;
            }
            $value = $row['VALUE'] ?? '';
            $values = is_array($value) ? $value : [$value];
            foreach ($values as $one) {
                if (!is_scalar($one)) {
                    continue;
                }
                $one = trim((string)$one);
                if ($one !== '') {
                    $result[$code][] = $one;
                }
            }
        }
        return $result;
    };

    foreach ($contentCodes as $code) {
        if (!isset($config['iblocks'][$code])) {
            throw new RuntimeException("010: инфоблок {$code} не объявлен в config/iblocks.php");
        }
        if (!isset($data[$code])) {
            throw new RuntimeException("010: нет данных для инфоблока {$code}");
        }

        $iblock = $config['iblocks'][$code];
        $iblockId = nlb_iblock_id($code);
        if ($iblockId <= 0) {
            throw new RuntimeException("010: инфоблок {$code} не найден (сначала 002)");
        }

        /* ---------------------------------------------------------- */
        /* 1. Свойства: имена, типы, кратность, сортировка, списки     */
        /* ---------------------------------------------------------- */

        foreach ($iblock['properties'] as $propertyCode => $property) {
            $multiple = (string)($property['multiple'] ?? 'N');
            $fields = [
                'NAME' => (string)$property['name'],
                'SORT' => (int)($property['sort'] ?? 100),
                'PROPERTY_TYPE' => (string)$property['type'],
                'MULTIPLE' => $multiple,
                'IS_REQUIRED' => (string)($property['required'] ?? 'N'),
                'SEARCHABLE' => (string)($property['searchable'] ?? 'N'),
                'FILTRABLE' => (string)($property['filtrable'] ?? 'N'),
                'USER_TYPE' => $property['user_type'] ?? false,
            ];

            $existing = nlb_property($iblockId, $propertyCode);
            if (!$existing) {
                $add = $fields;
                $add['IBLOCK_ID'] = $iblockId;
                $add['CODE'] = $propertyCode;
                $add['ACTIVE'] = 'Y';
                $add['LIST_TYPE'] = $multiple === 'Y' ? 'C' : 'L';

                if (($property['type'] ?? '') === 'L' && !empty($property['values'])) {
                    $add['VALUES'] = [];
                    foreach ($property['values'] as $value) {
                        $add['VALUES'][] = [
                            'VALUE' => $value['value'],
                            'XML_ID' => $value['xml_id'],
                            'SORT' => (int)($value['sort'] ?? 100),
                            'DEF' => !empty($value['def']) ? 'Y' : 'N',
                        ];
                    }
                }

                $object = new CIBlockProperty();
                $id = (int)$object->Add($add);
                if ($id <= 0) {
                    $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
                    throw new RuntimeException("010: свойство {$code}.{$propertyCode}: " . ($error ?: 'не создано'));
                }
                $created++;
                $notes[] = "создано свойство {$code}.{$propertyCode} (ID {$id})";
            } else {
                $diff = [];
                foreach (['NAME', 'SORT', 'PROPERTY_TYPE', 'MULTIPLE', 'IS_REQUIRED', 'SEARCHABLE', 'FILTRABLE', 'USER_TYPE'] as $field) {
                    $currentValue = $field === 'SORT'
                        ? (int)($existing[$field] ?? 0)
                        : (string)($existing[$field] ?? '');
                    $targetValue = $field === 'SORT'
                        ? (int)$fields[$field]
                        : (string)$fields[$field];
                    if ($currentValue !== $targetValue) {
                        $diff[$field] = $fields[$field];
                    }
                }

                if ($diff) {
                    $object = new CIBlockProperty();
                    if (!$object->Update((int)$existing['ID'], $diff)) {
                        $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
                        throw new RuntimeException("010: свойство {$code}.{$propertyCode}: " . ($error ?: 'не обновлено'));
                    }
                    $updated++;
                    $notes[] = "обновлено свойство {$code}.{$propertyCode}: " . implode(', ', array_keys($diff));
                } else {
                    $skipped++;
                }
            }

            /* Значения списков: недостающие дописываем, существующие
               переименовываем, если конфиг изменился. */
            if (($property['type'] ?? '') !== 'L' || empty($property['values'])) {
                continue;
            }

            $propertyId = nlb_property_id($iblockId, $propertyCode);
            foreach ($property['values'] as $value) {
                $xmlId = (string)$value['xml_id'];
                $enumId = nlb_enum_id($iblockId, $propertyCode, $xmlId);
                $enumFields = [
                    'VALUE' => (string)$value['value'],
                    'SORT' => (int)($value['sort'] ?? 100),
                    'DEF' => !empty($value['def']) ? 'Y' : 'N',
                ];

                if ($enumId <= 0) {
                    $enum = new CIBlockPropertyEnum();
                    $newId = (int)$enum->Add([
                        'PROPERTY_ID' => $propertyId,
                        'XML_ID' => $xmlId,
                        'VALUE' => $enumFields['VALUE'],
                        'SORT' => $enumFields['SORT'],
                        'DEF' => $enumFields['DEF'],
                    ]);
                    if ($newId <= 0) {
                        $error = isset($enum->LAST_ERROR) ? (string)$enum->LAST_ERROR : '';
                        throw new RuntimeException("010: значение {$code}.{$propertyCode}.{$xmlId}: " . ($error ?: 'не создано'));
                    }
                    $created++;
                    $notes[] = "добавлено значение {$code}.{$propertyCode}.{$xmlId}";
                    continue;
                }

                $res = CIBlockPropertyEnum::GetList(['SORT' => 'ASC'], ['ID' => $enumId]);
                $current = $res->Fetch();
                if (!$current) {
                    continue;
                }
                $enumDiff = [];
                foreach (['VALUE', 'SORT', 'DEF'] as $field) {
                    $currentValue = $field === 'SORT'
                        ? (int)($current[$field] ?? 0)
                        : (string)($current[$field] ?? '');
                    $targetValue = $field === 'SORT'
                        ? (int)$enumFields[$field]
                        : (string)$enumFields[$field];
                    if ($currentValue !== $targetValue) {
                        $enumDiff[$field] = $enumFields[$field];
                    }
                }
                if ($enumDiff) {
                    $enum = new CIBlockPropertyEnum();
                    if (!$enum->Update($enumId, $enumDiff)) {
                        $error = isset($enum->LAST_ERROR) ? (string)$enum->LAST_ERROR : '';
                        throw new RuntimeException("010: значение {$code}.{$propertyCode}.{$xmlId}: " . ($error ?: 'не обновлено'));
                    }
                    $updated++;
                    $notes[] = "обновлено значение {$code}.{$propertyCode}.{$xmlId}";
                }
            }
        }

        /* ---------------------------------------------------------- */
        /* 2. Разделы: названия и сортировка                           */
        /* ---------------------------------------------------------- */

        foreach ($iblock['sections_data'] as $section) {
            $sectionCode = (string)$section['code'];
            $sectionId = nlb_section_id($iblockId, $sectionCode);
            $fields = [
                'NAME' => (string)$section['name'],
                'SORT' => (int)($section['sort'] ?? 100),
                'XML_ID' => 'nlb-sec-' . $code . '-' . $sectionCode,
            ];

            if ($sectionId <= 0) {
                $object = new CIBlockSection();
                $newId = (int)$object->Add([
                    'IBLOCK_ID' => $iblockId,
                    'CODE' => $sectionCode,
                    'ACTIVE' => 'Y',
                    'DESCRIPTION_TYPE' => 'text',
                ] + $fields);
                if ($newId <= 0) {
                    $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
                    throw new RuntimeException("010: раздел {$code}.{$sectionCode}: " . ($error ?: 'не создан'));
                }
                $created++;
                $notes[] = "создан раздел {$code}.{$sectionCode} (ID {$newId})";
                continue;
            }

            $res = CIBlockSection::GetList(
                ['SORT' => 'ASC'],
                ['ID' => $sectionId, 'IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'],
                false,
                ['ID', 'NAME', 'SORT', 'XML_ID']
            );
            $current = $res->Fetch();
            if (!$current) {
                continue;
            }

            $diff = [];
            foreach (['NAME', 'SORT', 'XML_ID'] as $field) {
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
            if ($diff) {
                $object = new CIBlockSection();
                if (!$object->Update($sectionId, $diff)) {
                    $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
                    throw new RuntimeException("010: раздел {$code}.{$sectionCode}: " . ($error ?: 'не обновлён'));
                }
                $updated++;
                $notes[] = "обновлён раздел {$code}.{$sectionCode}: " . implode(', ', array_keys($diff));
            } else {
                $skipped++;
            }
        }

        /* ---------------------------------------------------------- */
        /* 3. Элементы, которых больше нет в конфиге                   */
        /* ---------------------------------------------------------- */

        $targetXmlIds = [];
        foreach ($data[$code]['items'] as $item) {
            $targetXmlIds[(string)$item['xml_id']] = true;
        }

        $obsolete = [];
        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID', 'XML_ID']
        );
        while ($row = $res->Fetch()) {
            $xmlId = (string)($row['XML_ID'] ?? '');
            if (!str_starts_with($xmlId, 'nlb-')) {
                continue;
            }
            if (!isset($targetXmlIds[$xmlId])) {
                $obsolete[] = ['ID' => (int)$row['ID'], 'XML_ID' => $xmlId];
            }
        }

        foreach ($obsolete as $row) {
            if (!CIBlockElement::Delete($row['ID'])) {
                throw new RuntimeException("010: не удалён устаревший элемент {$row['XML_ID']} (ID {$row['ID']})");
            }
            $updated++;
            $notes[] = "удалён устаревший элемент {$row['XML_ID']} (ID {$row['ID']})";
        }

        /* ---------------------------------------------------------- */
        /* 4. Элементы: обновить существующие, создать недостающие      */
        /* ---------------------------------------------------------- */

        foreach ($data[$code]['items'] as $item) {
            $xmlId = (string)$item['xml_id'];
            $activeFrom = (string)$item['active_from'];
            $eventDate = (string)($item['properties']['DATE'] ?? '');
            if ($code === 'events' && $eventDate !== '') {
                $activeFrom = $eventDate;
            }

            $sectionCode = (string)($item['section'] ?? '');
            $sectionId = 0;
            if ($sectionCode !== '') {
                $sectionId = nlb_section_id($iblockId, $sectionCode);
                if ($sectionId <= 0) {
                    throw new RuntimeException("010: элемент {$xmlId}: раздел {$sectionCode} не найден");
                }
            }

            /* Целевые значения всех свойств инфоблока: отсутствующие в
               конфиге элемента очищаем, чтобы книжные поля (например,
               AGE_LIMIT) не оставались в карточке сувенира. */
            $targetValues = nlb_prepare_property_values(
                $iblock,
                is_array($item['properties'] ?? null) ? $item['properties'] : [],
                $iblockId
            );
            foreach ($iblock['properties'] as $propertyCode => $property) {
                if (array_key_exists($propertyCode, $targetValues)) {
                    continue;
                }
                $targetValues[$propertyCode] = ($property['multiple'] ?? 'N') === 'Y' ? [] : false;
            }

            $fields = [
                'NAME' => (string)$item['name'],
                'CODE' => (string)$item['code'],
                'ACTIVE' => 'Y',
                'DATE_ACTIVE_FROM' => nlb_iso_to_site($activeFrom),
                'PREVIEW_TEXT' => (string)($item['preview_text'] ?? ''),
                'PREVIEW_TEXT_TYPE' => 'text',
                'DETAIL_TEXT' => (string)($item['detail_text'] ?? ''),
                'DETAIL_TEXT_TYPE' => 'html',
                'SORT' => (int)($item['sort'] ?? 500),
                'IBLOCK_SECTION_ID' => $sectionId > 0 ? $sectionId : false,
            ];

            $elementId = nlb_element_id($iblockId, $xmlId);
            if ($elementId <= 0) {
                $add = $fields;
                $add['IBLOCK_ID'] = $iblockId;
                $add['XML_ID'] = $xmlId;
                $add['PROPERTY_VALUES'] = $targetValues;

                $picture = nlb_file_array($item['image'] ?? null);
                if ($picture) {
                    $add['PREVIEW_PICTURE'] = $picture;
                    $add['DETAIL_PICTURE'] = $picture;
                }

                $object = new CIBlockElement();
                $newId = (int)$object->Add($add);
                if ($newId <= 0) {
                    $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
                    throw new RuntimeException("010: элемент {$xmlId}: " . ($error ?: 'не создан'));
                }
                $created++;
                $notes[] = "создан элемент {$xmlId} (ID {$newId})";
                continue;
            }

            $res = CIBlockElement::GetList(
                ['ID' => 'ASC'],
                ['ID' => $elementId, 'IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'],
                false,
                false,
                [
                    'ID', 'NAME', 'CODE', 'ACTIVE', 'DATE_ACTIVE_FROM',
                    'PREVIEW_TEXT', 'DETAIL_TEXT', 'PREVIEW_PICTURE',
                    'DETAIL_PICTURE', 'IBLOCK_SECTION_ID', 'SORT',
                ]
            );
            $current = $res->Fetch();
            if (!$current) {
                throw new RuntimeException("010: элемент {$xmlId} не прочитан");
            }

            $diff = [];
            foreach (['NAME', 'CODE', 'ACTIVE', 'PREVIEW_TEXT', 'DETAIL_TEXT'] as $field) {
                if ((string)($current[$field] ?? '') !== (string)$fields[$field]) {
                    $diff[$field] = $fields[$field];
                }
            }
            if ((int)($current['SORT'] ?? 0) !== (int)$fields['SORT']) {
                $diff['SORT'] = $fields['SORT'];
            }
            if ((int)($current['IBLOCK_SECTION_ID'] ?? 0) !== (int)$sectionId) {
                $diff['IBLOCK_SECTION_ID'] = $sectionId > 0 ? $sectionId : false;
            }

            $currentDate = MakeTimeStamp((string)($current['DATE_ACTIVE_FROM'] ?? ''));
            $targetDate = MakeTimeStamp((string)$fields['DATE_ACTIVE_FROM']);
            if ($currentDate !== $targetDate) {
                $diff['DATE_ACTIVE_FROM'] = $fields['DATE_ACTIVE_FROM'];
            }

            $currentValues = $elementValues($iblockId, $elementId);
            $propertyDiff = [];
            foreach ($targetValues as $propertyCode => $value) {
                $currentValue = $currentValues[$propertyCode] ?? [];
                if ($normalize($currentValue) !== $normalize($value)) {
                    $propertyDiff[$propertyCode] = $value;
                }
            }
            if ($propertyDiff) {
                $diff['PROPERTY_VALUES'] = $propertyDiff;
            }

            /* Картинка: у сувениров её нет — старую книжную обложку
               снимаем; у печати и услуг досоздаём, если её не было. */
            $hasImage = !empty($item['image']);
            $currentPreview = (int)($current['PREVIEW_PICTURE'] ?? 0);
            $currentDetail = (int)($current['DETAIL_PICTURE'] ?? 0);
            if ($hasImage && $currentPreview <= 0) {
                $picture = nlb_file_array($item['image']);
                if ($picture) {
                    $diff['PREVIEW_PICTURE'] = $picture;
                    $diff['DETAIL_PICTURE'] = $picture;
                }
            } elseif (!$hasImage && ($currentPreview > 0 || $currentDetail > 0)) {
                $diff['PREVIEW_PICTURE'] = ['del' => 'Y'];
                $diff['DETAIL_PICTURE'] = ['del' => 'Y'];
            }

            if (!$diff) {
                $skipped++;
                continue;
            }

            $object = new CIBlockElement();
            if (!$object->Update($elementId, $diff)) {
                $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
                throw new RuntimeException("010: элемент {$xmlId}: " . ($error ?: 'не обновлён'));
            }
            $updated++;
            $notes[] = "обновлён элемент {$xmlId}: " . implode(', ', array_keys($diff));
        }
    }

    return [
        'created' => $created,
        'updated' => $updated,
        'skipped' => $skipped,
        'notes' => $notes,
    ];
};
