<?php
/**
 * 003 — свойства инфоблоков и значения списков.
 *
 * Создаёт свойства из db/config/iblocks.php. Для существующих списков
 * дописывает недостающие значения по XML_ID, поэтому файл можно
 * расширять без пересоздания инфоблока.
 */

return static function (): array {
    $config = nlb_load_config('iblocks');
    $created = 0;
    $skipped = 0;
    $notes = [];

    foreach ($config['iblocks'] as $code => $iblock) {
        $iblockId = nlb_iblock_id($code);
        if ($iblockId <= 0) {
            throw new RuntimeException("свойства {$code}: инфоблок не найден (сначала 002)");
        }

        foreach ($iblock['properties'] as $propertyCode => $property) {
            $existing = nlb_property($iblockId, $propertyCode);

            if ($existing) {
                $skipped++;
            } else {
                $multiple = $property['multiple'] ?? 'N';
                $fields = [
                    'IBLOCK_ID' => $iblockId,
                    'NAME' => $property['name'],
                    'CODE' => $propertyCode,
                    'ACTIVE' => 'Y',
                    'SORT' => (int)($property['sort'] ?? 100),
                    'PROPERTY_TYPE' => $property['type'],
                    'MULTIPLE' => $multiple,
                    'IS_REQUIRED' => $property['required'] ?? 'N',
                    'SEARCHABLE' => $property['searchable'] ?? 'N',
                    'FILTRABLE' => $property['filtrable'] ?? 'N',
                    'LIST_TYPE' => $multiple === 'Y' ? 'C' : 'L',
                    'USER_TYPE' => $property['user_type'] ?? false,
                ];

                if (($property['type'] ?? '') === 'L' && !empty($property['values'])) {
                    $fields['VALUES'] = [];
                    foreach ($property['values'] as $value) {
                        $fields['VALUES'][] = [
                            'VALUE' => $value['value'],
                            'XML_ID' => $value['xml_id'],
                            'SORT' => (int)($value['sort'] ?? 100),
                            'DEF' => !empty($value['def']) ? 'Y' : 'N',
                        ];
                    }
                }

                $object = new CIBlockProperty();
                $id = (int)$object->Add($fields);
                if ($id <= 0) {
                    $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
                    throw new RuntimeException("свойство {$code}.{$propertyCode}: " . ($error ?: 'не удалось создать'));
                }

                $created++;
                $notes[] = "создано свойство {$code}.{$propertyCode} (ID {$id})";
                continue;
            }

            /* Списки: дописываем недостающие значения. */
            if (($property['type'] ?? '') === 'L' && !empty($property['values'])) {
                $propertyId = (int)$existing['ID'];
                $added = 0;

                foreach ($property['values'] as $value) {
                    $xmlId = (string)$value['xml_id'];
                    if (nlb_enum_id($iblockId, $propertyCode, $xmlId) > 0) {
                        continue;
                    }
                    $enum = new CIBlockPropertyEnum();
                    $enumId = (int)$enum->Add([
                        'PROPERTY_ID' => $propertyId,
                        'VALUE' => $value['value'],
                        'XML_ID' => $xmlId,
                        'SORT' => (int)($value['sort'] ?? 100),
                        'DEF' => !empty($value['def']) ? 'Y' : 'N',
                    ]);
                    if ($enumId <= 0) {
                        $error = isset($enum->LAST_ERROR) ? (string)$enum->LAST_ERROR : '';
                        throw new RuntimeException("значение {$code}.{$propertyCode}.{$xmlId}: " . ($error ?: 'не удалось создать'));
                    }
                    $added++;
                }

                if ($added > 0) {
                    $notes[] = "в список {$code}.{$propertyCode} добавлено значений: {$added}";
                }
            }
        }
    }

    return ['created' => $created, 'skipped' => $skipped, 'notes' => $notes];
};
