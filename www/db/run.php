<?php
/**
 * NLB Bootstrap — запуск миграций базы данных.
 *
 * CLI:
 *   php db/run.php                      — все шаги по порядку
 *   php db/run.php --only=001,002       — только выбранные шаги
 *   php db/run.php --force              — перезаписать menu/*.menu.php
 *   php db/run.php --set-template       — привязать шаблон к сайту (шаг 007)
 *
 * Веб (если нет CLI):
 *   /db/run.php?run=yes                 — под администратором
 *
 * Повторный запуск безопасен: каждая сущность ищется по CODE/XML_ID.
 * Лог пишется в db/logs/run-<дата>.log.
 */

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli';

/* ------------------------------------------------------------------ */
/* 1. Разбор аргументов (до загрузки ядра — чтобы работал --help)      */
/* ------------------------------------------------------------------ */

$options = [
    'only' => [],
    'force' => false,
    'set_template' => false,
    'help' => false,
];

$args = $isCli ? array_slice($argv, 1) : [];

foreach ($args as $arg) {
    if ($arg === '--help' || $arg === '-h') {
        $options['help'] = true;
    } elseif ($arg === '--force') {
        $options['force'] = true;
    } elseif ($arg === '--set-template') {
        $options['set_template'] = true;
    } elseif (str_starts_with($arg, '--only=')) {
        foreach (explode(',', substr($arg, 7)) as $part) {
            if (trim($part) !== '') {
                $options['only'][] = (int)trim($part);
            }
        }
    }
}

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    if (isset($_GET['only'])) {
        foreach (explode(',', (string)$_GET['only']) as $part) {
            if (trim($part) !== '') {
                $options['only'][] = (int)trim($part);
            }
        }
    }
    $options['force'] = !empty($_GET['force']);
    $options['set_template'] = !empty($_GET['set_template']);
}

if ($options['help']) {
    echo "NLB Bootstrap — миграции базы данных\n\n";
    echo "  php db/run.php                  все шаги\n";
    echo "  php db/run.php --only=001,005   выборочно\n";
    echo "  php db/run.php --force          перезаписать меню\n";
    echo "  php db/run.php --set-template   привязать шаблон к сайту\n";
    echo "\nШаги:\n";
    foreach (glob(__DIR__ . '/migrations/*.php') ?: [] as $file) {
        $name = basename($file, '.php');
        echo '  ' . substr($name, 0, 3) . ' — ' . str_replace('_', ' ', substr($name, 4)) . "\n";
    }
    exit(0);
}

/* ------------------------------------------------------------------ */
/* 2. Загрузка ядра и прав                                            */
/* ------------------------------------------------------------------ */

require __DIR__ . '/lib/boot.php';
nlb_require_admin();

if (!$isCli && ($_GET['run'] ?? '') !== 'yes') {
    http_response_code(400);
    echo "NLB: запуск не подтверждён. Откройте /db/run.php?run=yes\n";
    exit(4);
}

$GLOBALS['NLB_OPTIONS'] = $options;

/* ------------------------------------------------------------------ */
/* 3. Миграции                                                        */
/* ------------------------------------------------------------------ */

$files = glob(__DIR__ . '/migrations/*.php') ?: [];
sort($files);

if (!$files) {
    nlb_say('NLB: в db/migrations нет ни одного файла.');
    exit(1);
}

nlb_say('NLB Bootstrap — миграции базы данных');
nlb_say('Корень сайта: ' . nlb_root());
nlb_say('Режим: ' . ($isCli ? 'cli' : 'web') . ', сайт: ' . nlb_site_id());
nlb_say(str_repeat('-', 60));

$started = microtime(true);
$failed = false;
$ran = 0;
$createdTotal = 0;
$updatedTotal = 0;
$skippedTotal = 0;

foreach ($files as $file) {
    $name = basename($file, '.php');
    $number = (int)substr($name, 0, 3);

    if ($options['only'] && !in_array($number, $options['only'], true)) {
        continue;
    }

    $label = str_replace('_', ' ', substr($name, 4));
    nlb_say('');
    nlb_say(sprintf('[%03d] %s', $number, $label));

    $migration = require $file;
    if (!is_callable($migration)) {
        nlb_say('  ошибка: файл не возвращает функцию');
        $failed = true;
        break;
    }

    $stepStarted = microtime(true);
    try {
        $result = $migration();
    } catch (Throwable $e) {
        nlb_say('  ошибка: ' . $e->getMessage());
        $failed = true;
        break;
    }

    $created = (int)($result['created'] ?? 0);
    $updated = (int)($result['updated'] ?? 0);
    $skipped = (int)($result['skipped'] ?? 0);
    $createdTotal += $created;
    $updatedTotal += $updated;
    $skippedTotal += $skipped;

    nlb_say(sprintf(
        '  создано: %d, изменено: %d, пропущено: %d (%.2f с)',
        $created,
        $updated,
        $skipped,
        microtime(true) - $stepStarted
    ));

    foreach (($result['notes'] ?? []) as $note) {
        nlb_say('  · ' . $note);
    }

    $ran++;
}

/* ------------------------------------------------------------------ */
/* 4. Итог                                                            */
/* ------------------------------------------------------------------ */

nlb_say('');
nlb_say(str_repeat('-', 60));

if ($failed) {
    nlb_say('ОСТАНОВЛЕНО на ошибке. Исправьте причину и запустите снова:');
    nlb_say('  php db/run.php --only=' . sprintf('%03d', $number + 1));
    nlb_say('Лог: ' . nlb_log_path());
    exit(1);
}

nlb_clear_cache();

nlb_say(sprintf(
    'Готово: шагов %d, создано объектов %d, изменено %d, пропущено %d, время %.2f с.',
    $ran,
    $createdTotal,
    $updatedTotal,
    $skippedTotal,
    microtime(true) - $started
));
nlb_say('Кеш сайта сброшен.');
nlb_say('Проверка: php db/check.php');
nlb_say('Лог: ' . nlb_log_path());
exit(0);
