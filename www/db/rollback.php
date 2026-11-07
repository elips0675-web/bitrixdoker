<?php
/**
 * NLB Bootstrap — откат миграций.
 *
 * Удаляет только то, что создали миграции: элементы и разделы с XML_ID
 * nlb-*, инфоблоки из db/config/iblocks.php и их типы. Чужие инфоблоки,
 * элементы и настройки сайта не затрагиваются.
 *
 * CLI:
 *   php db/rollback.php --yes            — удалить структуру и контент
 *   php db/rollback.php --yes --menus    — плюс файлы /menu/*.menu.php
 *
 * Веб (под администратором): /db/rollback.php?rollback=yes&confirm=yes
 */

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli';

$confirmed = false;
$removeMenus = false;

if ($isCli) {
    $args = array_slice($argv, 1);
    $confirmed = in_array('--yes', $args, true);
    $removeMenus = in_array('--menus', $args, true);
} else {
    header('Content-Type: text/plain; charset=utf-8');
    $confirmed = ($_GET['rollback'] ?? '') === 'yes' && ($_GET['confirm'] ?? '') === 'yes';
    $removeMenus = !empty($_GET['menus']);
}

if (!$confirmed) {
    echo "NLB: откат удаляет инфоблоки, элементы и разделы, созданные миграциями.\n";
    echo "CLI:  php db/rollback.php --yes [--menus]\n";
    echo "Веб:  /db/rollback.php?rollback=yes&confirm=yes\n";
    exit(1);
}

require __DIR__ . '/lib/boot.php';
nlb_require_admin();

$config = nlb_load_config('iblocks');
$root = nlb_root();

$deletedElements = 0;
$deletedSections = 0;
$deletedIblocks = 0;
$deletedTypes = 0;

nlb_say('NLB Bootstrap — откат миграций');
nlb_say(str_repeat('-', 60));

/* 1. Элементы и разделы */
foreach ($config['iblocks'] as $code => $iblock) {
    $iblockId = nlb_iblock_id($code);
    if ($iblockId <= 0) {
        continue;
    }

    $res = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'XML_ID']
    );
    while ($row = $res->Fetch()) {
        if (!str_starts_with((string)$row['XML_ID'], 'nlb-')) {
            continue;
        }
        if (CIBlockElement::Delete((int)$row['ID'])) {
            $deletedElements++;
        } else {
            nlb_say("  ! не удалён элемент {$row['XML_ID']} (ID {$row['ID']})");
        }
    }

    $res = CIBlockSection::GetList(
        ['ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['ID', 'XML_ID']
    );
    while ($row = $res->Fetch()) {
        if (!str_starts_with((string)$row['XML_ID'], 'nlb-sec-')) {
            continue;
        }
        if (CIBlockSection::Delete((int)$row['ID'])) {
            $deletedSections++;
        } else {
            nlb_say("  ! не удалён раздел {$row['XML_ID']} (ID {$row['ID']})");
        }
    }
}

/* 2. Инфоблоки */
foreach ($config['iblocks'] as $code => $iblock) {
    $iblockId = nlb_iblock_id($code);
    if ($iblockId <= 0) {
        continue;
    }
    if (CIBlock::Delete($iblockId)) {
        $deletedIblocks++;
        nlb_say("  удалён инфоблок {$code} (ID {$iblockId})");
    } else {
        nlb_say("  ! не удалён инфоблок {$code} (ID {$iblockId})");
    }
}

/* 3. Типы */
foreach ($config['types'] as $typeId => $type) {
    if (!nlb_iblock_type_exists($typeId)) {
        continue;
    }
    if (CIBlockType::Delete($typeId)) {
        $deletedTypes++;
        nlb_say("  удалён тип {$typeId}");
    } else {
        nlb_say("  ! не удалён тип {$typeId}");
    }
}

/* 4. Меню */
if ($removeMenus) {
    foreach (['top', 'bottom'] as $menuType) {
        $file = $root . '/menu/' . $menuType . '.menu.php';
        if (is_file($file) && @unlink($file)) {
            nlb_say("  удалён /menu/{$menuType}.menu.php");
        }
    }
    foreach (['services', 'printing'] as $section) {
        $file = $root . '/' . $section . '/.left.menu.php';
        if (is_file($file) && @unlink($file)) {
            nlb_say("  удалён /{$section}/.left.menu.php");
        }
    }
}

nlb_clear_cache();

nlb_say('');
nlb_say(sprintf(
    'Откат завершён: элементы %d, разделы %d, инфоблоки %d, типы %d.',
    $deletedElements,
    $deletedSections,
    $deletedIblocks,
    $deletedTypes
));
nlb_say('Кеш сайта сброшен. Лог: ' . nlb_log_path());

exit(0);
