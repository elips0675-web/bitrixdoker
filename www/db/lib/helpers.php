<?php
/**
 * NLB Bootstrap — помощники миграций.
 *
 * Все функции работают только после загрузки ядра (db/lib/boot.php):
 * используют CIBlock*, CFile, ConvertTimeStamp.
 *
 * Поиск сущностей идёт по стабильным ключам (CODE, XML_ID), а не по ID:
 * на разных установках ID разные, коды — одинаковые.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!function_exists('nlb_option')) {
    /**
     * Опция запуска (--force, --set-template и т.п.).
     */
    function nlb_option(string $key, $default = null)
    {
        return $GLOBALS['NLB_OPTIONS'][$key] ?? $default;
    }
}

if (!function_exists('nlb_load_config')) {
    function nlb_load_config(string $name): array
    {
        $file = nlb_root() . '/db/config/' . $name . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Нет файла конфигурации: ' . $file);
        }
        $data = require $file;
        if (!is_array($data)) {
            throw new RuntimeException('Конфигурация ' . $name . ' должна возвращать массив');
        }
        return $data;
    }
}

if (!function_exists('nlb_load_data')) {
    /**
     * Демо-контент: массив [код инфоблока => config/data/<код>.php].
     */
    function nlb_load_data(): array
    {
        $files = glob(nlb_root() . '/db/config/data/*.php') ?: [];
        sort($files);
        $result = [];
        foreach ($files as $file) {
            $data = require $file;
            if (!is_array($data) || !isset($data['iblock'], $data['items'])) {
                throw new RuntimeException('Некорректный файл данных: ' . $file);
            }
            $result[(string)$data['iblock']] = $data;
        }
        return $result;
    }
}

if (!function_exists('nlb_site_id')) {
    /**
     * ID первого активного сайта (обычно s1). В CLI константа SITE_ID
     * не определена, поэтому запрашиваем её из базы.
     */
    function nlb_site_id(): string
    {
        static $siteId = null;
        if ($siteId !== null) {
            return $siteId;
        }
        if (defined('SITE_ID') && SITE_ID) {
            return $siteId = (string)SITE_ID;
        }
        $res = CSite::GetList('sort', 'asc', ['ACTIVE' => 'Y']);
        if ($row = $res->Fetch()) {
            return $siteId = (string)$row['LID'];
        }
        return $siteId = 's1';
    }
}

if (!function_exists('nlb_iblock_id')) {
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
        if ($row = $res->Fetch()) {
            $id = (int)$row['ID'];
        }
        // Ноль не кешируем: инфоблок может быть создан позже в этом же запуске.
        if ($id > 0) {
            $cache[$code] = $id;
        }
        return $id;
    }
}

if (!function_exists('nlb_iblock_type_exists')) {
    function nlb_iblock_type_exists(string $typeId): bool
    {
        $res = CIBlockType::GetList(['SORT' => 'ASC'], ['=ID' => $typeId]);
        return (bool)$res->Fetch();
    }
}

if (!function_exists('nlb_iblock_relax_code')) {
    /**
     * Снимает обязательность CODE и XML_ID у инфоблока формы.
     *
     * CIBlock::SetFields() этого не может: в его GetFieldsDefaults() у XML_ID
     * IS_REQUIRED = 'Y', и значение из $arFields игнорируется. Публичные формы
     * код не передают — он генерируется в OnBeforeIBlockElementAdd
     * (local/php_interface/include/nlb_forms.php), но проверка обязательных
     * полей идёт раньше события, поэтому нужен UPDATE b_iblock_fields.
     */
    function nlb_iblock_relax_code(int $iblockId): void
    {
        global $DB;
        $iblockId = (int)$iblockId;
        if ($iblockId <= 0 || !is_object($DB)) {
            return;
        }
        $DB->Query(
            "UPDATE b_iblock_fields SET IS_REQUIRED = 'N' "
            . 'WHERE IBLOCK_ID = ' . $iblockId
            . " AND FIELD_ID IN ('CODE', 'XML_ID')"
        );
        if (method_exists('CIBlock', 'CleanCache')) {
            CIBlock::CleanCache($iblockId);
        }
    }
}

if (!function_exists('nlb_property')) {
    /**
     * Строка свойства инфоблока: ID, CODE, PROPERTY_TYPE, MULTIPLE, USER_TYPE.
     */
    function nlb_property(int $iblockId, string $code): ?array
    {
        $res = CIBlockProperty::GetList([], [
            'IBLOCK_ID' => $iblockId,
            'CODE' => $code,
        ]);
        $row = $res->Fetch();
        return $row ?: null;
    }
}

if (!function_exists('nlb_property_id')) {
    function nlb_property_id(int $iblockId, string $code): int
    {
        $row = nlb_property($iblockId, $code);
        return $row ? (int)$row['ID'] : 0;
    }
}

if (!function_exists('nlb_enum_id')) {
    function nlb_enum_id(int $iblockId, string $propertyCode, string $xmlId): int
    {
        $propertyId = nlb_property_id($iblockId, $propertyCode);
        if ($propertyId <= 0) {
            return 0;
        }
        $res = CIBlockPropertyEnum::GetList(['SORT' => 'ASC'], [
            'PROPERTY_ID' => $propertyId,
            'XML_ID' => $xmlId,
        ]);
        $row = $res->Fetch();
        return $row ? (int)$row['ID'] : 0;
    }
}

if (!function_exists('nlb_section_id')) {
    function nlb_section_id(int $iblockId, string $code): int
    {
        $res = CIBlockSection::GetList(['SORT' => 'ASC'], [
            'IBLOCK_ID' => $iblockId,
            'CODE' => $code,
            'CHECK_PERMISSIONS' => 'N',
        ], false, ['ID']);
        $row = $res->Fetch();
        return $row ? (int)$row['ID'] : 0;
    }
}

if (!function_exists('nlb_element_id')) {
    function nlb_element_id(int $iblockId, string $xmlId): int
    {
        $res = CIBlockElement::GetList(['ID' => 'ASC'], [
            'IBLOCK_ID' => $iblockId,
            'XML_ID' => $xmlId,
            'CHECK_PERMISSIONS' => 'N',
        ], false, false, ['ID']);
        $row = $res->Fetch();
        return $row ? (int)$row['ID'] : 0;
    }
}

if (!function_exists('nlb_element_count')) {
    function nlb_element_count(int $iblockId): int
    {
        return (int)CIBlock::GetElementCount($iblockId);
    }
}

if (!function_exists('nlb_iblock_count')) {
    /**
     * Количество активных элементов инфоблока по его коду.
     *
     * Это замена «счётчикам»: просмотров, заказов и прочей статистики в
     * структуре нет, поэтому на сайте показывается только количество
     * материалов. Результат кешируется на время запроса.
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
            /* Запасной путь: результат без SelectedRowsCount (некоторые
               обёртки и заглушки). Для больших каталогов используйте
               SelectedRowsCount — он не тянет строки. */
            while ($res->Fetch()) {
                $count++;
            }
        }

        return $cache[$code] = $count;
    }
}

if (!function_exists('nlb_counts')) {
    /**
     * Количество материалов по всем инфоблокам сайта.
     *
     * Используется на страницах «О библиотеке» и в блоке цифр на главной:
     * счётчиков нет, показываем реальное количество записей.
     *
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

if (!function_exists('nlb_iso_to_site')) {
    /**
     * ISO-дата из конфига → формат сайта (для DATE_ACTIVE_FROM и свойств
     * «Дата/Время»). Формат берётся из настроек сайта, а не хардкодится.
     */
    function nlb_iso_to_site(string $iso): string
    {
        $timestamp = MakeTimeStamp($iso, 'YYYY-MM-DD HH:MI:SS');
        if (!$timestamp) {
            throw new RuntimeException('Не разобрана дата: ' . $iso);
        }
        return (string)ConvertTimeStamp($timestamp, 'FULL');
    }
}

if (!function_exists('nlb_prepare_property_values')) {
    /**
     * Значения свойств из конфига → формат CIBlockElement::Add.
     * Списки переводятся из XML_ID в ID значений, даты — в формат сайта.
     */
    function nlb_prepare_property_values(array $iblockConfig, array $values, int $iblockId): array
    {
        $prepared = [];
        foreach ($values as $code => $value) {
            $property = $iblockConfig['properties'][$code] ?? null;
            if (!$property) {
                continue;
            }
            $type = $property['type'] ?? 'S';

            if ($type === 'L') {
                $xmlIds = is_array($value) ? $value : [$value];
                $enumIds = [];
                foreach ($xmlIds as $xmlId) {
                    $enumId = nlb_enum_id($iblockId, $code, (string)$xmlId);
                    if ($enumId > 0) {
                        $enumIds[] = $enumId;
                    }
                }
                $prepared[$code] = ($property['multiple'] ?? 'N') === 'Y'
                    ? $enumIds
                    : ($enumIds[0] ?? '');
            } elseif (($property['user_type'] ?? null) === 'DateTime') {
                $prepared[$code] = nlb_iso_to_site((string)$value);
            } elseif ($type === 'N') {
                $prepared[$code] = (int)$value;
            } else {
                // Множественное строковое свойство (например, список особенностей).
                $prepared[$code] = ($property['multiple'] ?? 'N') === 'Y'
                    ? array_values(array_map(static function ($one) {
                        return (string)$one;
                    }, (array)$value))
                    : (string)$value;
            }
        }
        return $prepared;
    }
}

if (!function_exists('nlb_file_array')) {
    /**
     * Картинка из assets шаблона → массив файла для CIBlockElement::Add.
     */
    function nlb_file_array(?string $relativePath): array
    {
        if (!$relativePath) {
            return [];
        }
        $path = nlb_root() . '/local/templates/nlb_bootstrap/' . ltrim($relativePath, '/');
        if (!is_file($path)) {
            throw new RuntimeException('Нет картинки: ' . $path);
        }
        $file = CFile::MakeFileArray($path);
        return is_array($file) ? $file : [];
    }
}

if (!function_exists('nlb_clear_cache')) {
    function nlb_clear_cache(): void
    {
        if (function_exists('BXClearCache')) {
            BXClearCache(true);
        }
        if (class_exists('CBitrixComponent')) {
            CBitrixComponent::clearComponentCache('bitrix:menu');
            CBitrixComponent::clearComponentCache('bitrix:breadcrumb');
        }
    }
}
