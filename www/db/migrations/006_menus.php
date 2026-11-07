<?php
/**
 * 006 — файлы меню.
 *
 * Копирует db/menu/top.menu.php и db/menu/bottom.menu.php в /menu/ корня
 * сайта. Существующие файлы по умолчанию не перезаписываются: меню правит
 * контент-менеджер в админке («Структура сайта → Меню»).
 *
 * Перезаписать: php db/run.php --only=006 --force
 */

return static function (): array {
    $root = nlb_root();
    $targetDir = $root . '/menu';
    $force = (bool)nlb_option('force', false);
    $created = 0;
    $skipped = 0;
    $notes = [];

    if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        throw new RuntimeException('не удалось создать каталог ' . $targetDir);
    }
    if (!is_writable($targetDir)) {
        throw new RuntimeException('каталог ' . $targetDir . ' недоступен для записи');
    }

    foreach (['top', 'bottom'] as $type) {
        $source = $root . '/db/menu/' . $type . '.menu.php';
        $target = $targetDir . '/' . $type . '.menu.php';

        if (!is_file($source)) {
            throw new RuntimeException('нет исходного меню: ' . $source);
        }
        if (is_file($target) && !$force) {
            $skipped++;
            $notes[] = "menu/{$type}.menu.php уже есть (пропущено)";
            continue;
        }

        if (!@copy($source, $target)) {
            throw new RuntimeException('не удалось скопировать меню в ' . $target);
        }

        $created++;
        $notes[] = "menu/{$type}.menu.php записан";
    }

    return ['created' => $created, 'skipped' => $skipped, 'notes' => $notes];
};
