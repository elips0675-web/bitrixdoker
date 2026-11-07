<?php
/**
 * NLB Bootstrap — проверка базы данных после миграций.
 *
 * Сверяет фактическое состояние с db/config/*: типы, инфоблоки, свойства,
 * значения списков, разделы, элементы, файлы меню и привязку шаблона.
 *
 * CLI: php db/check.php
 * Веб: /db/check.php (под администратором)
 *
 * Выход: 0 — всё на месте, 1 — есть расхождения.
 */

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

require __DIR__ . '/lib/boot.php';
nlb_require_admin();

$config = nlb_load_config('iblocks');
$data = nlb_load_data();

$errors = [];
$warnings = [];

function nlb_check_fail(string $message): void
{
    global $errors;
    $errors[] = $message;
}

function nlb_check_warn(string $message): void
{
    global $warnings;
    $warnings[] = $message;
}

nlb_say('NLB Bootstrap — проверка базы данных');
nlb_say('Корень сайта: ' . nlb_root());
nlb_say(str_repeat('-', 60));

/* 1. Типы */
foreach ($config['types'] as $typeId => $type) {
    if (!nlb_iblock_type_exists($typeId)) {
        nlb_check_fail("нет типа инфоблоков {$typeId} ({$type['name']})");
    }
}
nlb_say('Типы инфоблоков: ' . count($config['types']));

/* 2. Инфоблоки и содержимое */
$rows = [];
foreach ($config['iblocks'] as $code => $iblock) {
    $iblockId = nlb_iblock_id($code);
    if ($iblockId <= 0) {
        nlb_check_fail("нет инфоблока {$code} ({$iblock['name']})");
        continue;
    }

    $properties = 0;
    foreach ($iblock['properties'] as $propertyCode => $property) {
        $row = nlb_property($iblockId, $propertyCode);
        if (!$row) {
            nlb_check_fail("{$code}: нет свойства {$propertyCode}");
            continue;
        }
        $properties++;

        if ((string)$row['PROPERTY_TYPE'] !== (string)$property['type']) {
            nlb_check_fail("{$code}.{$propertyCode}: тип {$row['PROPERTY_TYPE']} вместо {$property['type']}");
        }
        $multiple = (string)($property['multiple'] ?? 'N');
        if ((string)$row['MULTIPLE'] !== $multiple) {
            nlb_check_fail("{$code}.{$propertyCode}: MULTIPLE={$row['MULTIPLE']} вместо {$multiple}");
        }

        if (($property['type'] ?? '') === 'L') {
            foreach ($property['values'] as $value) {
                if (nlb_enum_id($iblockId, $propertyCode, (string)$value['xml_id']) <= 0) {
                    nlb_check_fail("{$code}.{$propertyCode}: нет значения {$value['xml_id']}");
                }
            }
        }
    }

    $sections = 0;
    foreach ($iblock['sections_data'] as $section) {
        if (nlb_section_id($iblockId, (string)$section['code']) <= 0) {
            nlb_check_fail("{$code}: нет раздела {$section['code']}");
        } else {
            $sections++;
        }
    }

    $expected = 0;
    $found = 0;
    if (isset($data[$code])) {
        foreach ($data[$code]['items'] as $item) {
            $expected++;
            if (nlb_element_id($iblockId, (string)$item['xml_id']) > 0) {
                $found++;
            } else {
                nlb_check_fail("{$code}: нет элемента {$item['xml_id']}");
            }
        }
    } elseif (empty($iblock['user_data'])) {
        nlb_check_warn("{$code}: нет файла демо-данных");
    }

    $rows[] = [$code, $iblockId, $properties, $sections, $found . '/' . $expected];
}

/* 3. Меню */
foreach (['top', 'bottom'] as $type) {
    $file = nlb_root() . '/menu/' . $type . '.menu.php';
    if (!is_file($file)) {
        nlb_check_fail("нет файла меню /menu/{$type}.menu.php (шаг 006)");
    }
}
foreach (['services', 'printing'] as $section) {
    $file = nlb_root() . '/' . $section . '/.left.menu.php';
    if (!is_file($file)) {
        nlb_check_fail("нет файла меню /{$section}/.left.menu.php (шаг 011)");
    }
}

/* 4. Шаблон сайта */
$siteId = nlb_site_id();
$templates = [];
$templateList = CSite::GetTemplateList($siteId);
while ($template = $templateList->Fetch()) {
    $templates[] = (string)($template['TEMPLATE'] ?? '');
}
if (!$templates) {
    nlb_check_warn("не удалось прочитать шаблоны сайта {$siteId}");
} elseif (!in_array('nlb_bootstrap', $templates, true)) {
    nlb_check_warn("к сайту {$siteId} не привязан шаблон nlb_bootstrap (шаг 007)");
}

/* 5. Таблица */
nlb_say('');
nlb_say(sprintf('%-12s %-6s %-8s %-8s %-10s', 'инфоблок', 'ID', 'свойств', 'разделов', 'элементы'));
nlb_say(str_repeat('-', 48));
foreach ($rows as $row) {
    nlb_say(sprintf('%-12s %-6d %-8d %-8d %-10s', $row[0], $row[1], $row[2], $row[3], $row[4]));
}

/* 6. Итог */
nlb_say('');
if ($warnings) {
    nlb_say('Предупреждения:');
    foreach ($warnings as $warning) {
        nlb_say('  ! ' . $warning);
    }
}

if ($errors) {
    nlb_say('Ошибки:');
    foreach ($errors as $error) {
        nlb_say('  x ' . $error);
    }
    nlb_say('');
    nlb_say('Проверка провалена: ' . count($errors) . ' ошибок. Запустите php db/run.php');
    exit(1);
}

nlb_say('OK: структура и демо-контент совпадают с db/config.');
exit(0);
