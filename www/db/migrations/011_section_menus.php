<?php
/**
 * 011 — меню разделов «Услуги» и «Печать».
 *
 * Копирует db/menu/services.left.menu.php и db/menu/printing.left.menu.php
 * в /services/.left.menu.php и /printing/.left.menu.php. Эти файлы дают
 * второй уровень верхнего меню (MAX_LEVEL = 2, CHILD_MENU_TYPE = left).
 *
 * Существующие файлы по умолчанию не перезаписываются: меню правит
 * контент-менеджер в админке («Структура сайта → Меню»).
 *
 * Перезаписать: php db/run.php --only=011 --force
 */

return static function (): array {
    $root = nlb_root();
    $force = (bool)nlb_option('force', false);
    $created = 0;
    $skipped = 0;
    $notes = [];

    $menus = [
        'services' => 'services.left.menu.php',
        'printing' => 'printing.left.menu.php',
    ];

    foreach ($menus as $section => $sourceName) {
        $source = $root . '/db/menu/' . $sourceName;
        $dir = $root . '/' . $section;
        $target = $dir . '/.left.menu.php';

        if (!is_file($source)) {
            throw new RuntimeException('нет исходного меню: ' . $source);
        }
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('не удалось создать каталог ' . $dir);
        }
        if (!is_writable($dir)) {
            throw new RuntimeException('каталог ' . $dir . ' недоступен для записи');
        }
        if (is_file($target) && !$force) {
            $skipped++;
            $notes[] = "{$section}/.left.menu.php уже есть (пропущено)";
            continue;
        }
        if (!@copy($source, $target)) {
            throw new RuntimeException('не удалось скопировать меню в ' . $target);
        }

        $created++;
        $notes[] = "{$section}/.left.menu.php записан";
    }

    return ['created' => $created, 'skipped' => $skipped, 'notes' => $notes];
};
