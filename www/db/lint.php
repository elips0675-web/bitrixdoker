<?php
/**
 * NLB Bootstrap — офлайн-линтер базы данных.
 *
 * Проверяет декларативные конфиги (db/config/*) без ядра Битрикса:
 * типы и коды, свойства, списки значений, разделы, демо-контент,
 * ссылки на картинки, иконки спрайта, файлы меню и миграции.
 *
 * Запуск:  php db/lint.php
 * Выход:   0 — ошибок нет, 1 — есть ошибки.
 *
 * Линтер не подключает Битрикс и ничего не пишет в базу — его можно
 * запускать на любой машине с PHP 8.0+.
 */

declare(strict_types=1);

$base = dirname(__DIR__);
$tpl = $base . '/local/templates/nlb_bootstrap';

$errors = [];
$warnings = [];
$checks = [];

function nlb_fail(string $message): void
{
    global $errors;
    $errors[] = $message;
}

function nlb_warn(string $message): void
{
    global $warnings;
    $warnings[] = $message;
}

function nlb_ok(string $message): void
{
    global $checks;
    $checks[] = $message;
}

/**
 * Плоский список элементов массива по ключу.
 */
function nlb_pluck(array $items, string $key): array
{
    $out = [];
    foreach ($items as $item) {
        if (isset($item[$key])) {
            $out[] = (string)$item[$key];
        }
    }
    return $out;
}

function nlb_is_iso_datetime(string $value): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
        return false;
    }
    $ts = strtotime($value);
    return $ts !== false && $ts > 0;
}

/* ------------------------------------------------------------------ */
/* 1. Структура                                                       */
/* ------------------------------------------------------------------ */

$configFile = $base . '/db/config/iblocks.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "нет файла: db/config/iblocks.php\n");
    exit(1);
}

$config = require $configFile;
if (!is_array($config) || !isset($config['types'], $config['iblocks'])) {
    fwrite(STDERR, "db/config/iblocks.php: ожидаются ключи types и iblocks\n");
    exit(1);
}

$types = $config['types'];
$iblocks = $config['iblocks'];

foreach ($types as $typeId => $type) {
    if (!preg_match('/^[a-z][a-z0-9_]{2,49}$/', (string)$typeId)) {
        nlb_fail("тип '{$typeId}': ID должен быть [a-z0-9_], 3–50 символов");
    }
    foreach (['name', 'sort', 'sections', 'lang'] as $key) {
        if (!array_key_exists($key, $type)) {
            nlb_fail("тип {$typeId}: нет ключа {$key}");
        }
    }
    if (!in_array($type['sections'] ?? '', ['Y', 'N'], true)) {
        nlb_fail("тип {$typeId}: sections должен быть Y или N");
    }
    foreach (['ru', 'en'] as $lang) {
        foreach (['name', 'section_name', 'element_name'] as $field) {
            if (empty($type['lang'][$lang][$field])) {
                nlb_fail("тип {$typeId}: нет lang.{$lang}.{$field}");
            }
        }
    }
}
nlb_ok('типов инфоблоков: ' . count($types));

if (!$iblocks) {
    nlb_fail('в конфиге нет ни одного инфоблока');
}

$iblockCodes = [];
foreach ($iblocks as $code => $iblock) {
    if (!preg_match('/^[a-z][a-z0-9_]{1,29}$/', (string)$code)) {
        nlb_fail("инфоблок '{$code}': код должен быть [a-z0-9_], 2–30 символов");
    }
    if (isset($iblockCodes[$code])) {
        nlb_fail("инфоблок {$code}: дубль кода");
    }
    $iblockCodes[] = $code;

    if (!isset($types[$iblock['type'] ?? ''])) {
        nlb_fail("инфоблок {$code}: неизвестный тип '{$iblock['type']}'");
    }
    foreach (['name', 'sort', 'list_page_url', 'detail_page_url', 'sections', 'properties', 'sections_data'] as $key) {
        if (!array_key_exists($key, $iblock)) {
            nlb_fail("инфоблок {$code}: нет ключа {$key}");
        }
    }
    if (!in_array($iblock['sections'] ?? '', ['Y', 'N'], true)) {
        nlb_fail("инфоблок {$code}: sections должен быть Y или N");
    }
    if (($iblock['sections'] ?? 'N') === 'N' && !empty($iblock['sections_data'])) {
        nlb_fail("инфоблок {$code}: sections=N, но sections_data заполнен");
    }

    /* Свойства */
    $propCodes = [];
    foreach ($iblock['properties'] as $propCode => $prop) {
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', (string)$propCode)) {
            nlb_fail("{$code}.{$propCode}: код свойства должен быть ВЕРХНИМ_РЕГИСТРОМ");
        }
        if (isset($propCodes[$propCode])) {
            nlb_fail("{$code}: дубль свойства {$propCode}");
        }
        $propCodes[] = $propCode;

        $propType = $prop['type'] ?? '';
        if (!in_array($propType, ['S', 'N', 'L', 'F'], true)) {
            nlb_fail("{$code}.{$propCode}: тип '{$propType}' не поддерживается линтером (S, N, L, F)");
        }
        if (!isset($prop['name'], $prop['sort'])) {
            nlb_fail("{$code}.{$propCode}: нужны name и sort");
        }

        $values = $prop['values'] ?? [];
        if ($propType === 'L') {
            if (!$values) {
                nlb_fail("{$code}.{$propCode}: список без значений");
            }
            $xmlIds = [];
            foreach ($values as $value) {
                $xmlId = (string)($value['xml_id'] ?? '');
                if (!preg_match('/^[a-z0-9_]+$/', $xmlId)) {
                    nlb_fail("{$code}.{$propCode}: XML_ID '{$xmlId}' должен быть [a-z0-9_]");
                }
                if (isset($xmlIds[$xmlId])) {
                    nlb_fail("{$code}.{$propCode}: дубль XML_ID '{$xmlId}'");
                }
                $xmlIds[$xmlId] = true;
                if (empty($value['value'])) {
                    nlb_fail("{$code}.{$propCode}: у значения '{$xmlId}' пустое название");
                }
            }
        } elseif ($values) {
            nlb_fail("{$code}.{$propCode}: values заданы для не-списка");
        }

        if (($prop['user_type'] ?? null) === 'DateTime' && $propType !== 'S') {
            nlb_fail("{$code}.{$propCode}: DateTime возможен только для типа S");
        }
    }

    /* Разделы */
    $sectionCodes = [];
    foreach ($iblock['sections_data'] as $section) {
        $sectionCode = (string)($section['code'] ?? '');
        if (!preg_match('/^[a-z0-9_-]+$/', $sectionCode)) {
            nlb_fail("{$code}: код раздела '{$sectionCode}' должен быть [a-z0-9_-]");
        }
        if (isset($sectionCodes[$sectionCode])) {
            nlb_fail("{$code}: дубль раздела {$sectionCode}");
        }
        $sectionCodes[$sectionCode] = true;
        if (empty($section['name'])) {
            nlb_fail("{$code}: у раздела {$sectionCode} пустое название");
        }
    }
}
nlb_ok('инфоблоков: ' . count($iblocks));

/* ------------------------------------------------------------------ */
/* 2. Демо-контент                                                    */
/* ------------------------------------------------------------------ */

$dataDir = $base . '/db/config/data';
$dataFiles = glob($dataDir . '/*.php') ?: [];
sort($dataFiles);

$totalItems = 0;
$xmlIds = [];
$perIblock = [];
$spriteIcons = [];

$headerFile = $tpl . '/header.php';
if (is_file($headerFile)) {
    $header = (string)file_get_contents($headerFile);
    preg_match_all('/<symbol\s+id="(i-[\w-]+)"/', $header, $matches);
    $spriteIcons = $matches[1];
}

foreach ($dataFiles as $file) {
    $name = basename($file, '.php');
    $data = require $file;
    if (!is_array($data) || !isset($data['iblock'], $data['items'])) {
        nlb_fail("data/{$name}.php: ожидаются ключи iblock и items");
        continue;
    }

    $iblockCode = (string)$data['iblock'];
    if (!isset($iblocks[$iblockCode])) {
        nlb_fail("data/{$name}.php: неизвестный инфоблок '{$iblockCode}'");
        continue;
    }
    if ($name !== $iblockCode) {
        nlb_warn("data/{$name}.php: имя файла не совпадает с инфоблоком '{$iblockCode}'");
    }

    $iblock = $iblocks[$iblockCode];
    $properties = $iblock['properties'];
    $sectionCodes = nlb_pluck($iblock['sections_data'], 'code');
    $itemCodes = [];
    $perIblock[$iblockCode] = count($data['items']);

    foreach ($data['items'] as $index => $item) {
        $label = "data/{$name}.php[" . ($index + 1) . ']';
        $totalItems++;

        $xmlId = (string)($item['xml_id'] ?? '');
        if (!preg_match('/^nlb-[a-z]+-\d{3}$/', $xmlId)) {
            nlb_fail("{$label}: xml_id '{$xmlId}' должен быть вида nlb-news-001");
        }
        if (isset($xmlIds[$xmlId])) {
            nlb_fail("{$label}: дубль xml_id '{$xmlId}' (уже был в {$xmlIds[$xmlId]})");
        }
        $xmlIds[$xmlId] = "data/{$name}.php[" . ($index + 1) . ']';

        $code = (string)($item['code'] ?? '');
        if (!preg_match('/^[a-z0-9-]+$/', $code)) {
            nlb_fail("{$label}: code '{$code}' должен быть [a-z0-9-]");
        }
        if (isset($itemCodes[$code])) {
            nlb_fail("{$label}: дубль code '{$code}' в инфоблоке {$iblockCode}");
        }
        $itemCodes[$code] = true;

        if (trim((string)($item['name'] ?? '')) === '') {
            nlb_fail("{$label}: пустое название");
        }

        $activeFrom = (string)($item['active_from'] ?? '');
        if (!nlb_is_iso_datetime($activeFrom)) {
            nlb_fail("{$label}: active_from '{$activeFrom}' должен быть ISO 'ГГГГ-ММ-ДД ЧЧ:ММ:СС'");
        }

        $section = (string)($item['section'] ?? '');
        if ($section !== '' && !in_array($section, $sectionCodes, true)) {
            nlb_fail("{$label}: раздел '{$section}' не объявлен в инфоблоке {$iblockCode}");
        }
        if (($iblock['sections'] ?? 'N') === 'Y' && $sectionCodes && $section === '' && $iblockCode !== 'books' && $iblockCode !== 'events' && $iblockCode !== 'news') {
            nlb_warn("{$label}: раздел не указан");
        }

        $image = $item['image'] ?? null;
        if ($image) {
            $path = $tpl . '/' . ltrim((string)$image, '/');
            if (!is_file($path)) {
                nlb_fail("{$label}: нет картинки {$image}");
            }
        }

        if (trim((string)($item['preview_text'] ?? '')) === '') {
            nlb_warn("{$label}: пустой анонс");
        }

        foreach (($item['properties'] ?? []) as $propCode => $value) {
            if (!isset($properties[$propCode])) {
                nlb_fail("{$label}: свойство {$propCode} не объявлено в инфоблоке {$iblockCode}");
                continue;
            }
            $prop = $properties[$propCode];

            if (($prop['type'] ?? '') === 'L') {
                $valueList = is_array($value) ? $value : [$value];
                $allowed = nlb_pluck($prop['values'], 'xml_id');
                foreach ($valueList as $enumValue) {
                    if (!in_array((string)$enumValue, $allowed, true)) {
                        nlb_fail("{$label}: значение '{$enumValue}' не найдено в списке {$propCode}");
                    }
                }
                if (($prop['multiple'] ?? 'N') !== 'Y' && is_array($value)) {
                    nlb_fail("{$label}: {$propCode} не множественное, а передан массив");
                }
            } elseif (($prop['user_type'] ?? null) === 'DateTime') {
                if (!nlb_is_iso_datetime((string)$value)) {
                    nlb_fail("{$label}: {$propCode} '{$value}' должен быть ISO 'ГГГГ-ММ-ДД ЧЧ:ММ:СС'");
                }
            } elseif (($prop['type'] ?? '') === 'N') {
                if (!is_int($value) && !is_float($value) && !preg_match('/^\d+$/', (string)$value)) {
                    nlb_fail("{$label}: {$propCode} '{$value}' должен быть числом");
                }
            } elseif (is_array($value)) {
                // Массив допустим только у множественного свойства (S/T).
                if (($prop['multiple'] ?? 'N') !== 'Y') {
                    nlb_fail("{$label}: {$propCode} не множественное, массив недопустим");
                }
            }
        }

        foreach ($properties as $propCode => $prop) {
            if (($prop['required'] ?? 'N') === 'Y' && !isset($item['properties'][$propCode])) {
                nlb_fail("{$label}: не заполнено обязательное свойство {$propCode}");
            }
        }

        if (!empty($properties['ICON'])) {
            $icon = (string)($item['properties']['ICON'] ?? '');
            if ($icon !== '' && $spriteIcons && !in_array('i-' . $icon, $spriteIcons, true)) {
                nlb_fail("{$label}: иконки i-{$icon} нет в спрайте header.php");
            }
        }
    }
}

nlb_ok('элементов демо-контента: ' . $totalItems);
foreach ($perIblock as $code => $count) {
    nlb_ok("  {$code}: {$count}");
}

/* ------------------------------------------------------------------ */
/* 3. Меню                                                            */
/* ------------------------------------------------------------------ */

$menuDir = $base . '/db/menu';
$menuFiles = [];
foreach (['top', 'bottom'] as $menuType) {
    $menuFiles[] = ['db/menu/' . $menuType . '.menu.php', 'меню ' . $menuType];
}
foreach (glob($menuDir . '/*.left.menu.php') ?: [] as $sectionMenuFile) {
    $sectionName = basename($sectionMenuFile, '.left.menu.php');
    $menuFiles[] = ['db/menu/' . $sectionName . '.left.menu.php', 'меню ' . $sectionName];
}

foreach ($menuFiles as [$menuRel, $menuLabel]) {
    $file = $base . '/' . $menuRel;
    if (!is_file($file)) {
        nlb_fail("нет файла меню: {$menuRel}");
        continue;
    }

    $aMenuLinks = null;
    ob_start();
    include $file;
    ob_end_clean();

    if (!is_array($aMenuLinks) || !$aMenuLinks) {
        nlb_fail("{$menuRel}: \$aMenuLinks не массив");
        continue;
    }
    foreach ($aMenuLinks as $i => $link) {
        if (!is_array($link) || count($link) < 2) {
            nlb_fail("{$menuRel}: пункт #{$i} должен быть массивом из 5 элементов");
            continue;
        }
        if (empty($link[0])) {
            nlb_fail("{$menuRel}: у пункта #{$i} нет текста");
        }
        if (!str_starts_with((string)$link[1], '/')) {
            nlb_fail("{$menuRel}: ссылка пункта #{$i} должна начинаться с /");
        }
    }
    nlb_ok("{$menuLabel}: " . count($aMenuLinks) . ' пунктов');
}

/* ------------------------------------------------------------------ */
/* 4. Миграции                                                        */
/* ------------------------------------------------------------------ */

$migrationFiles = glob($base . '/db/migrations/*.php') ?: [];
sort($migrationFiles);

$expected = 1;
foreach ($migrationFiles as $file) {
    $name = basename($file);
    if (!preg_match('/^(\d{3})_[a-z0-9_]+\.php$/', $name, $m)) {
        nlb_fail("migrations/{$name}: имя должно быть NNN_name.php");
        continue;
    }
    if ((int)$m[1] !== $expected) {
        nlb_fail("migrations/{$name}: ожидался номер " . sprintf('%03d', $expected));
    }
    $expected++;

    $migration = require $file;
    if (!is_callable($migration)) {
        nlb_fail("migrations/{$name}: файл должен возвращать функцию");
    }
}
nlb_ok('миграций: ' . count($migrationFiles));

foreach (['run.php', 'check.php', 'rollback.php', 'lib/boot.php', 'lib/helpers.php', 'sql/checks.sql', 'README.md'] as $rel) {
    if (!is_file($base . '/db/' . $rel)) {
        nlb_fail("нет файла: db/{$rel}");
    }
}
nlb_ok('раннер и проверки на месте');

/* ------------------------------------------------------------------ */
/* Итог                                                               */
/* ------------------------------------------------------------------ */

if ($warnings) {
    echo "Предупреждения:\n";
    foreach ($warnings as $message) {
        echo "  ! {$message}\n";
    }
}

if ($errors) {
    echo "Ошибки:\n";
    foreach ($errors as $message) {
        echo "  x {$message}\n";
    }
    echo "\nЛинтер провален: " . count($errors) . " ошибок.\n";
    exit(1);
}

echo "OK:\n";
foreach ($checks as $message) {
    echo "  + {$message}\n";
}
echo "\nЛинтер пройден: 0 ошибок.\n";
