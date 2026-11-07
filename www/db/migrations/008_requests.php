<?php
/**
 * 008 — заявки с сайта: инфоблоки requests, bookings, orders.
 *
 * Шаг нужен уже работающим установкам: 002/003 создают эти инфоблоки только
 * на чистой базе, а здесь они досоздаются по тем же правилам из
 * db/config/iblocks.php. Повторный запуск ничего не дублирует.
 *
 * Кроме структуры шаг заводит почтовое событие NLB_NEW_REQUEST: письмо —
 * только уведомление, сама заявка хранится в инфоблоке.
 */

return static function (): array {
    $config = nlb_load_config('iblocks');
    $siteId = nlb_site_id();
    $codes = ['requests', 'bookings', 'orders'];
    $created = 0;
    $skipped = 0;
    $notes = [];

    foreach ($codes as $code) {
        $iblock = $config['iblocks'][$code] ?? null;
        if (!$iblock) {
            throw new RuntimeException("008: в db/config/iblocks.php нет инфоблока {$code}");
        }

        $iblockId = nlb_iblock_id($code);

        if ($iblockId <= 0) {
            $fields = [
                'ACTIVE' => 'Y',
                'NAME' => $iblock['name'],
                'CODE' => $code,
                'IBLOCK_TYPE_ID' => $iblock['type'],
                'SITE_ID' => [$siteId],
                'SORT' => (int)$iblock['sort'],
                'LIST_PAGE_URL' => $iblock['list_page_url'],
                'DETAIL_PAGE_URL' => $iblock['detail_page_url'],
                'SECTION_PAGE_URL' => $iblock['section_page_url'],
                'INDEX_ELEMENT' => $iblock['index'] ?? 'Y',
                'INDEX_SECTION' => $iblock['sections'],
                'WORKFLOW' => 'N',
                'BIZPROC' => 'N',
                'SECTION_CHOOSER' => 'L',
                'LIST_MODE' => '',
                'VERSION' => 2,
                'GROUP_ID' => $iblock['groups'] ?? ['2' => 'R'],
            ];

            $object = new CIBlock();
            $iblockId = (int)$object->Add($fields);
            if ($iblockId <= 0) {
                $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
                throw new RuntimeException("инфоблок {$code}: " . ($error ?: 'не удалось создать'));
            }

            $created++;
            $notes[] = "создан инфоблок {$code} (ID {$iblockId})";
        } else {
            $skipped++;
            $notes[] = "инфоблок {$code} уже есть (ID {$iblockId})";

            /* Старые установки: у инфоблока могло не быть права на запись.
               Без него анонимная форма не добавит элемент. */
            if (method_exists('CIBlock', 'SetPermission')) {
                CIBlock::SetPermission($iblockId, $iblock['groups'] ?? ['2' => 'R']);
            }
        }

        foreach ($iblock['properties'] as $propertyCode => $property) {
            $existing = nlb_property($iblockId, $propertyCode);

            if ($existing) {
                $skipped++;
                continue;
            }

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
            $propertyId = (int)$object->Add($fields);
            if ($propertyId <= 0) {
                $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
                throw new RuntimeException("свойство {$code}.{$propertyCode}: " . ($error ?: 'не удалось создать'));
            }

            $created++;
            $notes[] = "создано свойство {$code}.{$propertyCode} (ID {$propertyId})";
        }
    }

    /* Почтовое событие. В заглушках ядра классов нет — шаг не падает,
       а честно сообщает, что уведомление не настроено. */
    $eventName = 'NLB_NEW_REQUEST';
    if (!class_exists('CEventType') || !class_exists('CEventMessage')) {
        $notes[] = 'почтовое событие ' . $eventName . ' пропущено: нет классов ядра (заглушки)';
        return ['created' => $created, 'skipped' => $skipped, 'notes' => $notes];
    }

    $typeExists = false;
    $typeRes = CEventType::GetList([], [
        'EVENT_NAME' => $eventName,
        'LID' => 'ru',
    ]);
    if ($typeRes && $typeRes->Fetch()) {
        $typeExists = true;
    }

    if (!$typeExists) {
        $type = new CEventType();
        $typeId = (int)$type->Add([
            'LID' => 'ru',
            'EVENT_NAME' => $eventName,
            'NAME' => 'Новая заявка с сайта',
            'DESCRIPTION' => 'Обращение, бронь услуги или заказ сувенира. Письмо-уведомление; сама заявка хранится в инфоблоке.',
            'SORT' => 100,
        ]);
        if ($typeId > 0) {
            $created++;
            $notes[] = 'создан тип почтового события ' . $eventName;
        } else {
            $error = isset($type->LAST_ERROR) ? (string)$type->LAST_ERROR : '';
            $notes[] = 'не удалось создать тип события ' . $eventName . ': ' . ($error ?: 'неизвестная ошибка');
        }
    } else {
        $skipped++;
    }

    $messageExists = false;
    $messageRes = CEventMessage::GetList('id', 'asc', [
        'EVENT_NAME' => $eventName,
        'LID' => $siteId,
    ]);
    if ($messageRes && $messageRes->Fetch()) {
        $messageExists = true;
    }

    if (!$messageExists) {
        $body = "Новая заявка с сайта.\n\n"
            . "Тип: #TYPE#\n"
            . "Имя: #NAME#\n"
            . "E-mail: #EMAIL#\n"
            . "Телефон: #PHONE#\n\n"
            . "Услуга: #SERVICE# (#SERVICE_CODE#)\n"
            . "Тариф: #TARIFF#\n"
            . "Дата: #DATE#\n"
            . "Время: #TIME#\n\n"
            . "Товар: #ITEM#\n"
            . "Артикул: #ARTICLE#\n"
            . "Цена: #PRICE#\n"
            . "Количество: #QTY#\n\n"
            . "Тема: #SUBJECT#\n"
            . "Сообщение: #MESSAGE#\n"
            . "Комментарий: #COMMENT#\n\n"
            . "Страница: #PAGE#\n"
            . "Заявка в админке: #LINK#\n";

        $message = new CEventMessage();
        $messageId = (int)$message->Add([
            'ACTIVE' => 'Y',
            'EVENT_NAME' => $eventName,
            'LID' => [$siteId],
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => 'info@example.by',
            'BCC' => '',
            'SUBJECT' => 'Новая заявка с сайта: #TYPE#',
            'BODY_TYPE' => 'text',
            'MESSAGE' => $body,
        ]);
        if ($messageId > 0) {
            $created++;
            $notes[] = 'создан почтовый шаблон ' . $eventName . ' (ID ' . $messageId . ')';
        } else {
            $error = isset($message->LAST_ERROR) ? (string)$message->LAST_ERROR : '';
            $notes[] = 'не удалось создать почтовый шаблон ' . $eventName . ': ' . ($error ?: 'неизвестная ошибка');
        }
    } else {
        $skipped++;
    }

    return ['created' => $created, 'skipped' => $skipped, 'notes' => $notes];
};
