<?php
/**
 * Заглушки классов ядра Битрикса для автотеста миграций.
 *
 * ВНИМАНИЕ: это не Битрикс. Заглушка повторяет только те методы и поля,
 * которые используют миграции NLB, и нужна, чтобы прогнать логику
 * (идемпотентность, списки, даты, откат) без установленного ядра.
 *
 * Проверка на реальном Битриксе — отдельный шаг (см. Что доделать.txt).
 */

define('B_PROLOG_INCLUDED', true);

final class NlbStubStore
{
    public static array $types = [];
    public static array $iblocks = [];
    public static array $properties = [];
    public static array $enums = [];
    public static array $sections = [];
    public static array $elements = [];
    public static array $templates = ['s1' => []];
    public static array $sequences = [];
    public static int $typeId = 0;
    public static int $iblockId = 0;
    public static int $propertyId = 0;
    public static int $enumId = 0;
    public static int $sectionId = 0;
    public static int $elementId = 0;
}

final class NlbStubResult
{
    private array $rows;
    private int $position = 0;

    public function __construct(array $rows)
    {
        $this->rows = array_values($rows);
    }

    public function Fetch()
    {
        if ($this->position >= count($this->rows)) {
            return false;
        }
        return $this->rows[$this->position++];
    }

    public function GetNext()
    {
        return $this->Fetch();
    }

    public function SelectedRowsCount(): int
    {
        return count($this->rows);
    }
}

function nlb_stub_filter(array $row, array $filter): bool
{
    foreach ($filter as $rawKey => $value) {
        $key = (string)$rawKey;
        if ($key === 'CHECK_PERMISSIONS' || $key === 'CNT_ACTIVE') {
            continue;
        }

        $operator = '';
        while ($key !== '' && in_array($key[0], ['=', '!', '%', '?', '<', '>'], true)) {
            $operator .= $key[0];
            $key = substr($key, 1);
        }

        $actual = array_key_exists($key, $row) ? (string)$row[$key] : '';

        if ($operator === '=') {
            if ($actual !== (string)$value) {
                return false;
            }
            continue;
        }
        if ($operator === '!') {
            if ($actual === (string)$value) {
                return false;
            }
            continue;
        }
        if ($operator === '%') {
            if (mb_stripos($actual, (string)$value) === false) {
                return false;
            }
            continue;
        }

        if (is_array($value)) {
            $candidates = array_map('strval', $value);
            if (!in_array($actual, $candidates, true)) {
                return false;
            }
            continue;
        }

        if (mb_strtolower($actual) !== mb_strtolower((string)$value)) {
            return false;
        }
    }
    return true;
}

function nlb_stub_sort(array $rows, array $order): array
{
    foreach (array_reverse($order, true) as $field => $direction) {
        usort($rows, static function (array $a, array $b) use ($field, $direction): int {
            $left = $a[$field] ?? '';
            $right = $b[$field] ?? '';
            $result = is_numeric($left) && is_numeric($right)
                ? (int)$left <=> (int)$right
                : strcmp((string)$left, (string)$right);
            return strtoupper((string)$direction) === 'DESC' ? -$result : $result;
        });
    }
    return $rows;
}

class CIBlockType
{
    public string $LAST_ERROR = '';

    public function Add(array $fields)
    {
        $id = (string)($fields['ID'] ?? '');
        if ($id === '' || isset(NlbStubStore::$types[$id])) {
            $this->LAST_ERROR = 'тип уже существует или пустой ID';
            return false;
        }
        NlbStubStore::$types[$id] = [
            'ID' => $id,
            'SORT' => (int)($fields['SORT'] ?? 500),
            'SECTIONS' => $fields['SECTIONS'] ?? 'Y',
        ];
        return $id;
    }

    public static function GetList(array $order = [], array $filter = []): NlbStubResult
    {
        $rows = [];
        foreach (NlbStubStore::$types as $type) {
            if (nlb_stub_filter($type, $filter)) {
                $rows[] = $type;
            }
        }
        return new NlbStubResult(nlb_stub_sort($rows, $order));
    }

    public static function Delete(string $id): bool
    {
        if (!isset(NlbStubStore::$types[$id])) {
            return false;
        }
        unset(NlbStubStore::$types[$id]);
        foreach (NlbStubStore::$iblocks as $iblockId => $iblock) {
            if ($iblock['IBLOCK_TYPE_ID'] === $id) {
                CIBlock::Delete((int)$iblockId);
            }
        }
        return true;
    }
}

class CIBlock
{
    public string $LAST_ERROR = '';

    public function Add(array $fields)
    {
        $code = (string)($fields['CODE'] ?? '');
        if ($code === '') {
            $this->LAST_ERROR = 'пустой CODE';
            return false;
        }
        foreach (NlbStubStore::$iblocks as $iblock) {
            if ($iblock['CODE'] === $code) {
                $this->LAST_ERROR = 'инфоблок с таким CODE уже есть';
                return false;
            }
        }
        $id = ++NlbStubStore::$iblockId;
        NlbStubStore::$iblocks[$id] = [
            'ID' => $id,
            'CODE' => $code,
            'NAME' => $fields['NAME'] ?? '',
            'IBLOCK_TYPE_ID' => $fields['IBLOCK_TYPE_ID'] ?? '',
            'TYPE' => $fields['IBLOCK_TYPE_ID'] ?? '',
            'ACTIVE' => $fields['ACTIVE'] ?? 'Y',
            'SORT' => (int)($fields['SORT'] ?? 500),
            'VERSION' => (int)($fields['VERSION'] ?? 1),
            'GROUP_ID' => $fields['GROUP_ID'] ?? [],
            'LIST_PAGE_URL' => $fields['LIST_PAGE_URL'] ?? '',
            'DETAIL_PAGE_URL' => $fields['DETAIL_PAGE_URL'] ?? '',
            'SECTION_PAGE_URL' => $fields['SECTION_PAGE_URL'] ?? '',
            'INDEX_ELEMENT' => $fields['INDEX_ELEMENT'] ?? 'Y',
            'INDEX_SECTION' => $fields['INDEX_SECTION'] ?? 'Y',
        ];
        NlbStubStore::$sequences[$id] = 0;
        return $id;
    }

    public static function GetList(array $order = [], array $filter = [], bool $count = false): NlbStubResult
    {
        $rows = [];
        foreach (NlbStubStore::$iblocks as $iblock) {
            if (nlb_stub_filter($iblock, $filter)) {
                $rows[] = $iblock;
            }
        }
        return new NlbStubResult(nlb_stub_sort($rows, $order));
    }

    public function Update(int $id, array $fields): bool
    {
        if (!isset(NlbStubStore::$iblocks[$id])) {
            $this->LAST_ERROR = 'нет инфоблока';
            return false;
        }
        foreach (['NAME', 'SORT', 'LIST_PAGE_URL', 'DETAIL_PAGE_URL', 'SECTION_PAGE_URL', 'INDEX_ELEMENT', 'INDEX_SECTION', 'ACTIVE'] as $field) {
            if (array_key_exists($field, $fields)) {
                NlbStubStore::$iblocks[$id][$field] = $field === 'SORT'
                    ? (int)$fields[$field]
                    : (string)$fields[$field];
            }
        }
        return true;
    }

    public static function Delete(int $id): bool
    {
        if (!isset(NlbStubStore::$iblocks[$id])) {
            return false;
        }
        unset(NlbStubStore::$iblocks[$id], NlbStubStore::$sequences[$id]);
        foreach (NlbStubStore::$properties as $propertyId => $property) {
            if ((int)$property['IBLOCK_ID'] === $id) {
                unset(NlbStubStore::$properties[$propertyId]);
            }
        }
        foreach (NlbStubStore::$enums as $enumId => $enum) {
            if (!isset(NlbStubStore::$properties[(int)$enum['PROPERTY_ID']])) {
                unset(NlbStubStore::$enums[$enumId]);
            }
        }
        foreach (NlbStubStore::$sections as $sectionId => $section) {
            if ((int)$section['IBLOCK_ID'] === $id) {
                unset(NlbStubStore::$sections[$sectionId]);
            }
        }
        foreach (NlbStubStore::$elements as $elementId => $element) {
            if ((int)$element['IBLOCK_ID'] === $id) {
                unset(NlbStubStore::$elements[$elementId]);
            }
        }
        return true;
    }

    public static function GetElementCount(int $id): int
    {
        $count = 0;
        foreach (NlbStubStore::$elements as $element) {
            if ((int)$element['IBLOCK_ID'] === $id) {
                $count++;
            }
        }
        return $count;
    }
}

class CIBlockProperty
{
    public string $LAST_ERROR = '';

    public function Add(array $fields)
    {
        $iblockId = (int)($fields['IBLOCK_ID'] ?? 0);
        if (!isset(NlbStubStore::$iblocks[$iblockId])) {
            $this->LAST_ERROR = 'нет инфоблока';
            return false;
        }
        $code = (string)($fields['CODE'] ?? '');
        foreach (NlbStubStore::$properties as $property) {
            if ((int)$property['IBLOCK_ID'] === $iblockId && $property['CODE'] === $code) {
                $this->LAST_ERROR = 'свойство уже есть';
                return false;
            }
        }

        $id = ++NlbStubStore::$propertyId;
        NlbStubStore::$properties[$id] = [
            'ID' => $id,
            'IBLOCK_ID' => $iblockId,
            'CODE' => $code,
            'NAME' => $fields['NAME'] ?? '',
            'PROPERTY_TYPE' => $fields['PROPERTY_TYPE'] ?? 'S',
            'MULTIPLE' => $fields['MULTIPLE'] ?? 'N',
            'USER_TYPE' => $fields['USER_TYPE'] ?: '',
            'IS_REQUIRED' => $fields['IS_REQUIRED'] ?? 'N',
            'SEARCHABLE' => $fields['SEARCHABLE'] ?? 'N',
            'FILTRABLE' => $fields['FILTRABLE'] ?? 'N',
            'SORT' => (int)($fields['SORT'] ?? 500),
        ];

        foreach (($fields['VALUES'] ?? []) as $value) {
            $enum = new CIBlockPropertyEnum();
            $enum->Add([
                'PROPERTY_ID' => $id,
                'VALUE' => $value['VALUE'] ?? '',
                'XML_ID' => $value['XML_ID'] ?? '',
                'SORT' => $value['SORT'] ?? 500,
                'DEF' => $value['DEF'] ?? 'N',
            ]);
        }

        return $id;
    }

    public static function GetList(array $order = [], array $filter = []): NlbStubResult
    {
        $rows = [];
        foreach (NlbStubStore::$properties as $property) {
            if (nlb_stub_filter($property, $filter)) {
                $rows[] = $property;
            }
        }
        return new NlbStubResult(nlb_stub_sort($rows, $order));
    }

    public function Update(int $id, array $fields): bool
    {
        if (!isset(NlbStubStore::$properties[$id])) {
            $this->LAST_ERROR = 'нет свойства';
            return false;
        }
        foreach (['NAME', 'PROPERTY_TYPE', 'MULTIPLE', 'USER_TYPE', 'IS_REQUIRED', 'SEARCHABLE', 'FILTRABLE', 'SORT'] as $field) {
            if (array_key_exists($field, $fields)) {
                NlbStubStore::$properties[$id][$field] = $field === 'SORT'
                    ? (int)$fields[$field]
                    : (string)$fields[$field];
            }
        }
        return true;
    }
}

class CIBlockPropertyEnum
{
    public string $LAST_ERROR = '';

    public function Add(array $fields)
    {
        $propertyId = (int)($fields['PROPERTY_ID'] ?? 0);
        if (!isset(NlbStubStore::$properties[$propertyId])) {
            $this->LAST_ERROR = 'нет свойства';
            return false;
        }
        $xmlId = (string)($fields['XML_ID'] ?? '');
        if ($xmlId !== '') {
            foreach (NlbStubStore::$enums as $enum) {
                if ((int)$enum['PROPERTY_ID'] === $propertyId && $enum['XML_ID'] === $xmlId) {
                    $this->LAST_ERROR = 'значение уже есть';
                    return false;
                }
            }
        }
        $id = ++NlbStubStore::$enumId;
        NlbStubStore::$enums[$id] = [
            'ID' => $id,
            'PROPERTY_ID' => $propertyId,
            'VALUE' => $fields['VALUE'] ?? '',
            'XML_ID' => $xmlId,
            'SORT' => (int)($fields['SORT'] ?? 500),
            'DEF' => $fields['DEF'] ?? 'N',
        ];
        return $id;
    }

    public static function GetList(array $order = [], array $filter = []): NlbStubResult
    {
        $rows = [];
        foreach (NlbStubStore::$enums as $enum) {
            if (nlb_stub_filter($enum, $filter)) {
                $rows[] = $enum;
            }
        }
        return new NlbStubResult(nlb_stub_sort($rows, $order));
    }

    public function Update(int $id, array $fields): bool
    {
        if (!isset(NlbStubStore::$enums[$id])) {
            $this->LAST_ERROR = 'нет значения списка';
            return false;
        }
        foreach (['VALUE', 'XML_ID', 'SORT', 'DEF'] as $field) {
            if (array_key_exists($field, $fields)) {
                NlbStubStore::$enums[$id][$field] = $field === 'SORT'
                    ? (int)$fields[$field]
                    : (string)$fields[$field];
            }
        }
        return true;
    }
}

class CIBlockSection
{
    public string $LAST_ERROR = '';

    public function Add(array $fields)
    {
        $iblockId = (int)($fields['IBLOCK_ID'] ?? 0);
        if (!isset(NlbStubStore::$iblocks[$iblockId])) {
            $this->LAST_ERROR = 'нет инфоблока';
            return false;
        }
        $code = (string)($fields['CODE'] ?? '');
        foreach (NlbStubStore::$sections as $section) {
            if ((int)$section['IBLOCK_ID'] === $iblockId && $section['CODE'] === $code) {
                $this->LAST_ERROR = 'раздел уже есть';
                return false;
            }
        }
        $id = ++NlbStubStore::$sectionId;
        NlbStubStore::$sections[$id] = [
            'ID' => $id,
            'IBLOCK_ID' => $iblockId,
            'CODE' => $code,
            'XML_ID' => $fields['XML_ID'] ?? '',
            'NAME' => $fields['NAME'] ?? '',
            'ACTIVE' => $fields['ACTIVE'] ?? 'Y',
            'SORT' => (int)($fields['SORT'] ?? 500),
        ];
        return $id;
    }

    public static function GetList(array $order = [], array $filter = [], bool $count = false, array $select = []): NlbStubResult
    {
        $rows = [];
        foreach (NlbStubStore::$sections as $section) {
            if (nlb_stub_filter($section, $filter)) {
                $rows[] = $section;
            }
        }
        return new NlbStubResult(nlb_stub_sort($rows, $order));
    }

    public function Update(int $id, array $fields): bool
    {
        if (!isset(NlbStubStore::$sections[$id])) {
            $this->LAST_ERROR = 'нет раздела';
            return false;
        }
        foreach (['CODE', 'XML_ID', 'NAME', 'ACTIVE', 'SORT'] as $field) {
            if (array_key_exists($field, $fields)) {
                NlbStubStore::$sections[$id][$field] = $field === 'SORT'
                    ? (int)$fields[$field]
                    : (string)$fields[$field];
            }
        }
        return true;
    }

    public static function Delete(int $id): bool
    {
        if (!isset(NlbStubStore::$sections[$id])) {
            return false;
        }
        unset(NlbStubStore::$sections[$id]);
        return true;
    }
}

class CIBlockElement
{
    public string $LAST_ERROR = '';

    public function Add(array $fields)
    {
        $iblockId = (int)($fields['IBLOCK_ID'] ?? 0);
        if (!isset(NlbStubStore::$iblocks[$iblockId])) {
            $this->LAST_ERROR = 'нет инфоблока';
            return false;
        }
        $xmlId = (string)($fields['XML_ID'] ?? '');
        foreach (NlbStubStore::$elements as $element) {
            if ((int)$element['IBLOCK_ID'] === $iblockId && $xmlId !== '' && $element['XML_ID'] === $xmlId) {
                $this->LAST_ERROR = 'элемент с таким XML_ID уже есть';
                return false;
            }
        }

        $id = ++NlbStubStore::$elementId;
        NlbStubStore::$sequences[$iblockId] = max(NlbStubStore::$sequences[$iblockId] ?? 0, $id);
        NlbStubStore::$elements[$id] = [
            'ID' => $id,
            'IBLOCK_ID' => $iblockId,
            'IBLOCK_SECTION_ID' => $fields['IBLOCK_SECTION_ID'] ?? null,
            'NAME' => $fields['NAME'] ?? '',
            'CODE' => $fields['CODE'] ?? '',
            'XML_ID' => $xmlId,
            'ACTIVE' => $fields['ACTIVE'] ?? 'Y',
            'DATE_ACTIVE_FROM' => $fields['DATE_ACTIVE_FROM'] ?? '',
            'PREVIEW_TEXT' => $fields['PREVIEW_TEXT'] ?? '',
            'PREVIEW_TEXT_TYPE' => $fields['PREVIEW_TEXT_TYPE'] ?? 'text',
            'DETAIL_TEXT' => $fields['DETAIL_TEXT'] ?? '',
            'DETAIL_TEXT_TYPE' => $fields['DETAIL_TEXT_TYPE'] ?? 'html',
            'SORT' => (int)($fields['SORT'] ?? 500),
            'PROPERTY_VALUES' => $fields['PROPERTY_VALUES'] ?? [],
            'PREVIEW_PICTURE' => isset($fields['PREVIEW_PICTURE']) ? (int)$fields['PREVIEW_PICTURE'] : 0,
            'DETAIL_PICTURE' => isset($fields['DETAIL_PICTURE']) ? (int)$fields['DETAIL_PICTURE'] : 0,
        ];
        return $id;
    }

    public static function GetList(
        array $order = [],
        array $filter = [],
        bool $count = false,
        $nav = false,
        array $select = []
    ): NlbStubResult {
        $rows = [];
        foreach (NlbStubStore::$elements as $element) {
            if (nlb_stub_filter($element, $filter)) {
                $rows[] = $element;
            }
        }
        return new NlbStubResult(nlb_stub_sort($rows, $order));
    }

    public static function Delete(int $id): bool
    {
        unset(NlbStubStore::$elements[$id]);
        return true;
    }

    public function Update(int $id, array $fields): bool
    {
        if (!isset(NlbStubStore::$elements[$id])) {
            $this->LAST_ERROR = 'нет элемента';
            return false;
        }

        foreach (['NAME', 'CODE', 'XML_ID', 'ACTIVE', 'DATE_ACTIVE_FROM', 'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE', 'DETAIL_TEXT', 'DETAIL_TEXT_TYPE', 'SORT'] as $field) {
            if (array_key_exists($field, $fields)) {
                NlbStubStore::$elements[$id][$field] = $field === 'SORT'
                    ? (int)$fields[$field]
                    : (string)$fields[$field];
            }
        }

        if (array_key_exists('IBLOCK_SECTION_ID', $fields)) {
            NlbStubStore::$elements[$id]['IBLOCK_SECTION_ID'] = $fields['IBLOCK_SECTION_ID'] ?: null;
        }

        foreach (['PREVIEW_PICTURE', 'DETAIL_PICTURE'] as $field) {
            if (!array_key_exists($field, $fields)) {
                continue;
            }
            $value = $fields[$field];
            if (is_array($value) && !empty($value['del'])) {
                NlbStubStore::$elements[$id][$field] = 0;
            } elseif (is_array($value) && !empty($value['tmp_name'])) {
                NlbStubStore::$elements[$id][$field] = 1;
            } else {
                NlbStubStore::$elements[$id][$field] = (int)$value;
            }
        }

        if (isset($fields['PROPERTY_VALUES']) && is_array($fields['PROPERTY_VALUES'])) {
            foreach ($fields['PROPERTY_VALUES'] as $code => $value) {
                $values = is_array($value) ? $value : [$value];
                $values = array_values(array_filter($values, static function ($one) {
                    return is_scalar($one) && trim((string)$one) !== '';
                }));
                if (!$values) {
                    unset(NlbStubStore::$elements[$id]['PROPERTY_VALUES'][$code]);
                    continue;
                }
                NlbStubStore::$elements[$id]['PROPERTY_VALUES'][$code] = is_array($value) ? $values : $values[0];
            }
        }

        return true;
    }

    public static function GetProperty($iblockId, $elementId, array $order = [], array $filter = [], array $select = []): NlbStubResult
    {
        $elementId = (int)$elementId;
        $element = NlbStubStore::$elements[$elementId] ?? null;
        if (!$element || (int)$element['IBLOCK_ID'] !== (int)$iblockId) {
            return new NlbStubResult([]);
        }

        $rows = [];
        foreach (($element['PROPERTY_VALUES'] ?? []) as $code => $value) {
            $propertyId = 0;
            $type = 'S';
            foreach (NlbStubStore::$properties as $property) {
                if ((int)$property['IBLOCK_ID'] === (int)$iblockId && $property['CODE'] === $code) {
                    $propertyId = (int)$property['ID'];
                    $type = (string)$property['PROPERTY_TYPE'];
                    break;
                }
            }

            $values = is_array($value) ? $value : [$value];
            foreach ($values as $one) {
                if (is_array($one)) {
                    $one = $one['VALUE'] ?? '';
                }
                $one = trim((string)$one);
                if ($one === '') {
                    continue;
                }
                $enumValue = '';
                if ($type === 'L') {
                    $enumValue = (string)(NlbStubStore::$enums[(int)$one]['VALUE'] ?? '');
                }
                $rows[] = [
                    'ID' => 0,
                    'IBLOCK_ELEMENT_ID' => $elementId,
                    'IBLOCK_PROPERTY_ID' => $propertyId,
                    'PROPERTY_ID' => $propertyId,
                    'CODE' => (string)$code,
                    'PROPERTY_TYPE' => $type,
                    'VALUE' => $one,
                    'VALUE_ENUM' => $enumValue,
                ];
            }
        }

        return new NlbStubResult($rows);
    }
}

class CSite
{
    public string $LAST_ERROR = '';

    public static function GetList(string $by = 'sort', string $order = 'asc', array $filter = []): NlbStubResult
    {
        $sites = [['LID' => 's1', 'NAME' => 'Библиотека', 'ACTIVE' => 'Y', 'SORT' => 100]];
        $rows = [];
        foreach ($sites as $site) {
            if (nlb_stub_filter($site, $filter)) {
                $rows[] = $site;
            }
        }
        return new NlbStubResult($rows);
    }

    public static function GetTemplateList(string $siteId): NlbStubResult
    {
        return new NlbStubResult(NlbStubStore::$templates[$siteId] ?? []);
    }

    public function Update(string $siteId, array $fields): bool
    {
        if (isset($fields['TEMPLATE'])) {
            NlbStubStore::$templates[$siteId] = array_map(static function (array $template): array {
                return [
                    'TEMPLATE' => (string)$template['TEMPLATE'],
                    'CONDITION' => (string)$template['CONDITION'],
                    'SORT' => (int)$template['SORT'],
                ];
            }, $fields['TEMPLATE']);
        }
        return true;
    }
}

class CFile
{
    public static function MakeFileArray(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        return [
            'name' => basename($path),
            'tmp_name' => $path,
            'type' => 'image/jpeg',
            'size' => filesize($path),
            'error' => 0,
        ];
    }
}

class CBitrixComponent
{
    public static function clearComponentCache(string $name, $template = false): void
    {
        // В заглушке кеша нет.
    }
}

function MakeTimeStamp($value, $format = '')
{
    if (is_numeric($value)) {
        return (int)$value;
    }
    $timestamp = strtotime((string)$value);
    return $timestamp === false ? 0 : $timestamp;
}

function ConvertTimeStamp($timestamp, $type = 'SHORT')
{
    if (!is_numeric($timestamp) || (int)$timestamp <= 0) {
        return '';
    }
    return (int)$type === 0 || $type === 'FULL'
        ? date('d.m.Y H:i:s', (int)$timestamp)
        : date('d.m.Y', (int)$timestamp);
}

function BXClearCache($full = false)
{
    return true;
}

/* ------------------------------------------------------------------ */
/* Состояние между запусками (файл из NLB_STUB_STATE)                  */
/* ------------------------------------------------------------------ */

function nlb_stub_state_path(): ?string
{
    $path = getenv('NLB_STUB_STATE');
    return ($path === false || $path === '') ? null : $path;
}

function nlb_stub_load(): void
{
    $path = nlb_stub_state_path();
    if ($path === null || !is_file($path)) {
        return;
    }
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return;
    }
    $state = json_decode($raw, true);
    if (!is_array($state)) {
        return;
    }
    foreach (['types', 'iblocks', 'properties', 'enums', 'sections', 'elements', 'templates', 'sequences'] as $key) {
        if (isset($state[$key]) && is_array($state[$key])) {
            NlbStubStore::${$key} = $state[$key];
        }
    }
    foreach (['typeId', 'iblockId', 'propertyId', 'enumId', 'sectionId', 'elementId'] as $key) {
        if (isset($state[$key])) {
            NlbStubStore::${$key} = (int)$state[$key];
        }
    }
}

function nlb_stub_save(): void
{
    $path = nlb_stub_state_path();
    if ($path === null) {
        return;
    }
    $state = [
        'types' => NlbStubStore::$types,
        'iblocks' => NlbStubStore::$iblocks,
        'properties' => NlbStubStore::$properties,
        'enums' => NlbStubStore::$enums,
        'sections' => NlbStubStore::$sections,
        'elements' => NlbStubStore::$elements,
        'templates' => NlbStubStore::$templates,
        'sequences' => NlbStubStore::$sequences,
        'typeId' => NlbStubStore::$typeId,
        'iblockId' => NlbStubStore::$iblockId,
        'propertyId' => NlbStubStore::$propertyId,
        'enumId' => NlbStubStore::$enumId,
        'sectionId' => NlbStubStore::$sectionId,
        'elementId' => NlbStubStore::$elementId,
    ];
    @file_put_contents($path, json_encode($state, JSON_UNESCAPED_UNICODE));
}

nlb_stub_load();
register_shutdown_function('nlb_stub_save');
