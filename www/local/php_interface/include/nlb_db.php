<?php
/**
 * NLB Bootstrap — помощники для страниц.
 *
 * Только чтение: поиск инфоблоков по коду, количество элементов, классы
 * оформления. Функции защищены function_exists, потому что тот же набор
 * объявлен в db/lib/helpers.php для миграций.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!function_exists('nlb_iblock_id')) {
    /**
     * ID своего инфоблока по символьному коду. Кешируется на время запроса.
     *
     * Код в Битриксе не уникален: тот же `CODE` может быть у инфоблока
     * другого типа (демо-контент другой редакции). Чужие инфоблоки
     * пропускаются — свой всегда лежит в типе с префиксом `nlb_`.
     * Конфиг миграций (`db/config/iblocks.php`) здесь недоступен: каталог
     * `db/` после установки удаляют.
     */
    function nlb_iblock_id(string $code): int
    {
        static $cache = [];
        if (array_key_exists($code, $cache)) {
            return $cache[$code];
        }

        $id = 0;
        $res = CIBlock::GetList(['SORT' => 'ASC'], [
            'CODE' => $code,
            'CHECK_PERMISSIONS' => 'N',
        ]);
        while ($row = $res->Fetch()) {
            if (!str_starts_with((string)($row['IBLOCK_TYPE_ID'] ?? ''), 'nlb_')) {
                continue;
            }
            $id = (int)$row['ID'];
            break;
        }

        // Ноль не кешируем: инфоблок может быть создан позже в этом же запуске.
        if ($id > 0) {
            $cache[$code] = $id;
        }

        return $id;
    }
}

if (!function_exists('nlb_iblock_count')) {
    /**
     * Количество активных элементов инфоблока по коду.
     *
     * Заменяет «счётчики»: в структуре нет просмотров и заказов, поэтому
     * показываем только количество материалов.
     */
    function nlb_iblock_count(string $code): int
    {
        static $cache = [];
        if (array_key_exists($code, $cache)) {
            return $cache[$code];
        }

        $iblockId = nlb_iblock_id($code);
        if ($iblockId <= 0) {
            return $cache[$code] = 0;
        }

        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            ['ID']
        );

        $count = method_exists($res, 'SelectedRowsCount')
            ? (int)$res->SelectedRowsCount()
            : 0;
        if ($count === 0) {
            while ($res->Fetch()) {
                $count++;
            }
        }

        return $cache[$code] = $count;
    }
}

if (!function_exists('nlb_counts')) {
    /**
     * @return array<string,int> код инфоблока => число активных элементов
     */
    function nlb_counts(): array
    {
        $counts = [];
        foreach (['news', 'events', 'books', 'collections', 'services', 'printing'] as $code) {
            $counts[$code] = nlb_iblock_count($code);
        }
        return $counts;
    }
}

if (!function_exists('nlb_iblock_sections')) {
    /**
     * Разделы инфоблока по его коду.
     *
     * @return array<int,array{ID:int,NAME:string,CODE:string}>
     */
    function nlb_iblock_sections(string $code): array
    {
        static $cache = [];
        if (isset($cache[$code])) {
            return $cache[$code];
        }

        $iblockId = nlb_iblock_id($code);
        if ($iblockId <= 0) {
            return $cache[$code] = [];
        }

        $rows = [];
        $res = CIBlockSection::GetList(
            ['SORT' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            ['ID', 'NAME', 'CODE']
        );
        while ($row = $res->Fetch()) {
            $rows[] = [
                'ID' => (int)$row['ID'],
                'NAME' => (string)$row['NAME'],
                'CODE' => (string)$row['CODE'],
            ];
        }

        return $cache[$code] = $rows;
    }
}

if (!function_exists('nlb_iblock_section_id')) {
    /**
     * ID раздела по коду внутри инфоблока; 0 — раздела нет.
     */
    function nlb_iblock_section_id(string $iblockCode, string $sectionCode): int
    {
        if ($sectionCode === '') {
            return 0;
        }
        foreach (nlb_iblock_sections($iblockCode) as $section) {
            if ($section['CODE'] === $sectionCode) {
                return $section['ID'];
            }
        }
        return 0;
    }
}

if (!function_exists('nlb_element_name')) {
    /**
     * Название элемента по коду — для <title> детальной страницы.
     *
     * Заголовок нужно знать до подключения header.php, поэтому шаблон
     * детальной страницы получает SET_TITLE => 'N'.
     */
    function nlb_element_name(string $iblockCode, string $elementCode): string
    {
        if ($elementCode === '') {
            return '';
        }
        $iblockId = nlb_iblock_id($iblockCode);
        if ($iblockId <= 0) {
            return '';
        }

        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'CODE' => $elementCode,
                'ACTIVE' => 'Y',
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            ['ID', 'NAME']
        );
        $row = $res->Fetch();
        return $row ? (string)$row['NAME'] : '';
    }
}

if (!function_exists('nlb_iblock_properties')) {
    /**
     * Свойства инфоблока по коду: CODE => строка CIBlockProperty::GetList.
     *
     * @return array<string,array>
     */
    function nlb_iblock_properties(string $iblockCode): array
    {
        static $cache = [];
        if (isset($cache[$iblockCode])) {
            return $cache[$iblockCode];
        }

        $iblockId = nlb_iblock_id($iblockCode);
        if ($iblockId <= 0) {
            return $cache[$iblockCode] = [];
        }

        $rows = [];
        $res = CIBlockProperty::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y']
        );
        while ($row = $res->Fetch()) {
            $rows[(string)$row['CODE']] = $row;
        }

        return $cache[$iblockCode] = $rows;
    }
}

if (!function_exists('nlb_property_enums')) {
    /**
     * Значения свойства-списка.
     *
     * @return array<int,array{ID:int,VALUE:string,XML_ID:string,DEF:string}>
     */
    function nlb_property_enums(int $propertyId): array
    {
        static $cache = [];
        if (isset($cache[$propertyId])) {
            return $cache[$propertyId];
        }

        $rows = [];
        if ($propertyId > 0) {
            $res = CIBlockPropertyEnum::GetList(
                ['SORT' => 'ASC', 'ID' => 'ASC'],
                ['PROPERTY_ID' => $propertyId]
            );
            while ($row = $res->Fetch()) {
                $rows[] = [
                    'ID' => (int)$row['ID'],
                    'VALUE' => (string)$row['VALUE'],
                    'XML_ID' => (string)$row['XML_ID'],
                    'DEF' => (string)($row['DEF'] ?? 'N'),
                ];
            }
        }

        return $cache[$propertyId] = $rows;
    }
}

if (!function_exists('nlb_element_values')) {
    /**
     * Значения свойств элемента по его символьному коду.
     *
     * @param string[] $codes пустой массив — все свойства
     * @return array<string,string>
     */
    function nlb_element_values(string $iblockCode, string $elementCode, array $codes = []): array
    {
        if ($elementCode === '') {
            return [];
        }

        $iblockId = nlb_iblock_id($iblockCode);
        if ($iblockId <= 0) {
            return [];
        }

        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'CODE' => $elementCode,
                'ACTIVE' => 'Y',
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            ['ID']
        );
        $row = $res->Fetch();
        if (!$row) {
            return [];
        }

        $values = [];
        $propertyRes = CIBlockElement::GetProperty(
            $iblockId,
            (int)$row['ID'],
            ['sort' => 'asc'],
            []
        );
        while ($property = $propertyRes->Fetch()) {
            $code = (string)($property['CODE'] ?? '');
            if ($code === '' || ($codes && !in_array($code, $codes, true))) {
                continue;
            }
            $value = $property['VALUE'] ?? '';
            if (is_array($value)) {
                $value = implode(', ', array_filter($value, static function ($one) {
                    return is_scalar($one) && (string)$one !== '';
                }));
            }
            $values[$code] = trim((string)$value);
        }

        return $values;
    }
}

if (!function_exists('nlb_color_class')) {
    /**
     * XML_ID цвета из свойства COLOR → суффикс класса `.icon-tile--*`.
     *
     * В разметке есть только blue, violet, green, orange, sky, поэтому
     * «primary» и «pink» переводятся в ближайшие к ним.
     */
    function nlb_color_class($color): string
    {
        $map = [
            'primary' => 'blue',
            'green' => 'green',
            'sky' => 'sky',
            'pink' => 'violet',
            'orange' => 'orange',
        ];
        $color = (string)$color;
        return $map[$color] ?? 'blue';
    }
}

if (!function_exists('nlb_grad_class')) {
    /**
     * XML_ID цвета → суффикс градиента `.bg-grad-*`.
     */
    function nlb_grad_class($color): string
    {
        $allowed = ['primary', 'green', 'sky', 'pink', 'orange'];
        $color = (string)$color;
        return in_array($color, $allowed, true) ? $color : 'primary';
    }
}

if (!function_exists('nlb_collection_class')) {
    /**
     * XML_ID цвета → суффикс фона карточки коллекции.
     */
    function nlb_collection_class($color): string
    {
        $map = [
            'primary' => 'blue',
            'green' => 'green',
            'sky' => 'sky',
            'pink' => 'violet',
            'orange' => 'orange',
        ];
        $color = (string)$color;
        return $map[$color] ?? 'blue';
    }
}

if (!function_exists('nlb_events_filter')) {
    /**
     * Фильтр афиши: прошедшие или предстоящие события.
     *
     * Диапазон дат в фильтре задаётся только ключом DATE_ACTIVE_FROM:
     * ключ ACTIVE_FROM ядро молча игнорирует, и страница «Прошедшие»
     * показывала те же будущие события, что и «Предстоящие» (P1-27).
     *
     * @return array<string,string>
     */
    function nlb_events_filter(bool $past, ?string $now = null): array
    {
        $now = $now ?? (string)ConvertTimeStamp(time(), 'FULL');
        return $past
            ? ['<DATE_ACTIVE_FROM' => $now]
            : ['>=DATE_ACTIVE_FROM' => $now];
    }
}

if (!function_exists('nlb_event_cat_class')) {
    /**
     * XML_ID категории события → класс бейджа.
     */
    function nlb_event_cat_class($xmlId): string
    {
        $map = [
            'literature' => 'bg-grad-sky',
            'children' => 'bg-grad-pink',
            'tech' => 'bg-grad-green',
            'lecture' => 'bg-grad-orange',
        ];
        $xmlId = (string)$xmlId;
        return $map[$xmlId] ?? 'bg-grad-sky';
    }
}

if (!function_exists('nlb_value')) {
    /**
     * Значение свойства элемента: строка, число или текст списка.
     */
    function nlb_value(array $item, string $code, string $default = ''): string
    {
        $property = $item['PROPERTIES'][$code] ?? null;
        if (!is_array($property)) {
            return $default;
        }
        $value = $property['VALUE'] ?? '';
        if (is_array($value)) {
            $value = implode(', ', array_filter($value, static function ($one) {
                return is_scalar($one) && (string)$one !== '';
            }));
        }
        $value = trim((string)$value);
        return $value !== '' ? $value : $default;
    }
}

if (!function_exists('nlb_values')) {
    /**
     * Все значения множественного свойства (например, FEATURES у услуги).
     *
     * @return string[]
     */
    function nlb_values(array $item, string $code): array
    {
        $property = $item['PROPERTIES'][$code] ?? null;
        if (!is_array($property)) {
            return [];
        }

        $value = $property['VALUE'] ?? [];
        $value = is_array($value) ? $value : [$value];
        $value = array_filter($value, static function ($one) {
            return is_scalar($one) && trim((string)$one) !== '';
        });

        return array_values(array_map(static function ($one) {
            return trim((string)$one);
        }, $value));
    }
}

if (!function_exists('nlb_xml_id')) {
    /**
     * XML_ID значения списка (например, категории события).
     */
    function nlb_xml_id(array $item, string $code): string
    {
        $property = $item['PROPERTIES'][$code] ?? null;
        if (!is_array($property)) {
            return '';
        }
        if (!empty($property['VALUE_XML_ID'])) {
            return (string)$property['VALUE_XML_ID'];
        }
        return '';
    }
}

if (!function_exists('nlb_price')) {
    /**
     * «от 300 ₽» из числа; 0 или пусто → пустая строка (бесплатно).
     */
    function nlb_price($value, string $suffix = '₽'): string
    {
        $number = (int)$value;
        if ($number <= 0) {
            return '';
        }
        return 'от ' . number_format($number, 0, '', ' ') . ' ' . $suffix;
    }
}
