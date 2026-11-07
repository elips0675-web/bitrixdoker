<?php
/**
 * NLB Bootstrap — автотест миграций на заглушках ядра.
 *
 * Что проверяется:
 *  - первый запуск создаёт 184 объектов (типы, инфоблоки, свойства, разделы,
 *    элементы, меню);
 *  - повторный запуск ничего не дублирует;
 *  - проверка db/check.php проходит;
 *  - значения свойств сконвертированы (дата, списки, картинки) и попадают
 *    в документы поиска из инфоблоков;
 *  - привязка шаблона (шаг 007) записывается;
 *  - на «книжной» базе шаги 009/010 дают сувениры и 0 изменений при повторе;
 *  - откат удаляет всё своё, после него check.php падает.
 *
 * Запуск: php db/tests/run.php
 * Выход:  0 — все проверки прошли, 1 — есть падения.
 *
 * Это не проверка на Битриксе: классы ядра подменены заглушками
 * (db/tests/stub). Реальная установка — отдельный пункт бэклога.
 */

declare(strict_types=1);

$package = dirname(__DIR__, 2);
$php = PHP_BINARY;
$temp = rtrim(sys_get_temp_dir(), '/\\') . '/nlb-db-test-' . getmypid();

$failures = [];
$checks = [];

function nlb_test_ok(string $message): void
{
    global $checks;
    $checks[] = $message;
    echo "  + {$message}\n";
}

function nlb_test_fail(string $message): void
{
    global $failures;
    $failures[] = $message;
    echo "  x {$message}\n";
}

function nlb_test_assert(bool $condition, string $message): void
{
    $condition ? nlb_test_ok($message) : nlb_test_fail($message);
}

function nlb_test_run(string $script, array $args, string $root): array
{
    global $php;

    putenv('NLB_ROOT=' . $root);
    $command = '"' . $php . '" "' . $script . '"';
    foreach ($args as $arg) {
        $command .= ' ' . $arg;
    }

    $output = [];
    $code = 0;
    exec($command . ' 2>&1', $output, $code);
    return [implode("\n", $output), $code];
}

function nlb_test_rmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir) ?: [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        if (is_link($path) || is_file($path)) {
            @unlink($path);
        } else {
            nlb_test_rmdir($path);
        }
    }
    @rmdir($dir);
}

function nlb_test_copy(string $source, string $target): bool
{
    if (is_file($source)) {
        @mkdir(dirname($target), 0775, true);
        return @copy($source, $target);
    }
    if (!is_dir($source)) {
        return false;
    }
    @mkdir($target, 0775, true);
    foreach (scandir($source) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        if (!nlb_test_copy($source . '/' . $item, $target . '/' . $item)) {
            return false;
        }
    }
    return true;
}

/**
 * Пробует симлинк, затем junction Windows, затем копирование.
 * Возвращает true, если создана ссылка (её надо снимать отдельно).
 */
function nlb_test_link(string $source, string $target): bool
{
    if (@symlink($source, $target)) {
        return true;
    }
    if (DIRECTORY_SEPARATOR === '\\') {
        $output = [];
        $code = 0;
        exec('cmd /c mklink /J "' . $target . '" "' . $source . '" 2>&1', $output, $code);
        if ($code === 0 && is_dir($target)) {
            return true;
        }
    }
    nlb_test_copy($source, $target);
    return false;
}

echo "NLB Bootstrap — автотест миграций (заглушки ядра)\n";
echo "Пакет: {$package}\n";
echo "Песочница: {$temp}\n";
echo str_repeat('-', 60) . "\n";

/* ------------------------------------------------------------------ */
/* 1. Песочница                                                       */
/* ------------------------------------------------------------------ */

nlb_test_rmdir($temp);
@mkdir($temp . '/bitrix/modules/main/include', 0775, true);

$linked = [];
$linked['db'] = nlb_test_link($package . '/db', $temp . '/db');
$linked['local'] = nlb_test_link($package . '/local', $temp . '/local');

$prolog = $temp . '/bitrix/modules/main/include/prolog_before.php';
$prologCode = "<?php\n"
    . "require " . var_export($package . '/db/tests/stub/bitrix.php', true) . ";\n"
    . "require " . var_export($package . '/db/tests/stub/loader.php', true) . ";\n";
file_put_contents($prolog, $prologCode);

putenv('NLB_STUB_STATE=' . $temp . '/state.json');

nlb_test_assert(is_file($prolog), 'создано фальшивое ядро (заглушки)');
nlb_test_assert(is_dir($temp . '/db/config'), 'каталог db доступен в песочнице');
nlb_test_assert(is_dir($temp . '/local/templates/nlb_bootstrap/assets/images'), 'шаблон доступен в песочнице');

/* ------------------------------------------------------------------ */
/* 2. Линтер и первый запуск                                          */
/* ------------------------------------------------------------------ */

[$output, $code] = nlb_test_run($package . '/db/lint.php', [], $temp);
nlb_test_assert($code === 0, 'lint.php: exit 0');

[$output, $code] = nlb_test_run($temp . '/db/run.php', [], $temp);
nlb_test_assert($code === 0, 'первый запуск: exit 0');
nlb_test_assert(str_contains($output, 'Готово'), 'первый запуск: есть итоговая строка');
nlb_test_assert(
    str_contains($output, 'создано объектов 184'),
    'первый запуск: создано 184 объектов (в выводе: '
    . (preg_match('/создано объектов (\d+)/', $output, $m) ? $m[1] : '?') . ')'
);
if ($code !== 0) {
    echo "\n--- вывод run.php ---\n{$output}\n";
}

/* ------------------------------------------------------------------ */
/* 3. Значения свойств                                                */
/* ------------------------------------------------------------------ */

[$output, $code] = nlb_test_run($temp . '/db/tests/inspect.php', [], $temp);
nlb_test_assert($code === 0, 'inspect.php: значения свойств и документы поиска корректны');
if ($code !== 0) {
    echo "\n--- вывод inspect.php ---\n{$output}\n";
}

/* ------------------------------------------------------------------ */
/* 4. Идемпотентность и привязка шаблона                              */
/* ------------------------------------------------------------------ */

[$output, $code] = nlb_test_run($temp . '/db/run.php', [], $temp);
nlb_test_assert($code === 0, 'повторный запуск: exit 0');
nlb_test_assert(
    str_contains($output, 'создано объектов 0'),
    'повторный запуск: новых объектов 0'
);

[$output, $code] = nlb_test_run($temp . '/db/run.php', ['--only=007', '--set-template'], $temp);
nlb_test_assert($code === 0, 'шаг 007 с --set-template: exit 0');
nlb_test_assert(str_contains($output, 'привязан'), 'шаблон привязан к сайту');

/* ------------------------------------------------------------------ */
/* 5. Проверка базы                                                   */
/* ------------------------------------------------------------------ */

[$output, $code] = nlb_test_run($temp . '/db/check.php', [], $temp);
nlb_test_assert($code === 0, 'check.php после миграций: exit 0');
if ($code !== 0) {
    echo "\n--- вывод check.php ---\n{$output}\n";
}

/* ------------------------------------------------------------------ */
/* 6. Существующая «книжная» база: миграции 009 и 010                 */
/* ------------------------------------------------------------------ */

[$output, $code] = nlb_test_run($temp . '/db/tests/legacy.php', [], $temp);
nlb_test_assert($code === 0, 'legacy.php: книжная база собрана');
if ($code !== 0) {
    echo "\n--- вывод legacy.php ---\n{$output}\n";
}

[$output, $code] = nlb_test_run($temp . '/db/run.php', ['--only=009'], $temp);
nlb_test_assert($code === 0, '009 на книжной базе: exit 0');
nlb_test_assert(
    (bool)preg_match('/изменено: [1-9]\d*/', $output),
    '009 на книжной базе: изменения есть'
);

[$output, $code] = nlb_test_run($temp . '/db/run.php', ['--only=010'], $temp);
nlb_test_assert($code === 0, '010 на книжной базе: exit 0');
nlb_test_assert(
    (bool)preg_match('/изменено: [1-9]\d*/', $output),
    '010 на книжной базе: изменения есть'
);

[$output, $code] = nlb_test_run($temp . '/db/tests/souvenirs.php', [], $temp);
nlb_test_assert($code === 0, 'souvenirs.php: книги стали сувенирами');
if ($code !== 0) {
    echo "\n--- вывод souvenirs.php ---\n{$output}\n";
}

[$output, $code] = nlb_test_run($temp . '/db/run.php', ['--only=009,010'], $temp);
nlb_test_assert($code === 0, 'повтор 009,010: exit 0');
nlb_test_assert(
    !preg_match('/создано: [1-9]/', $output) && !preg_match('/изменено: [1-9]/', $output),
    'повтор 009,010: 0 изменений'
);
if (preg_match('/создано: [1-9]|изменено: [1-9]/', $output)) {
    echo "\n--- вывод повтора ---\n{$output}\n";
}

[$output, $code] = nlb_test_run($temp . '/db/check.php', [], $temp);
nlb_test_assert($code === 0, 'check.php после 009,010: exit 0');
if ($code !== 0) {
    echo "\n--- вывод check.php ---\n{$output}\n";
}

/* ------------------------------------------------------------------ */
/* 7. Откат                                                           */
/* ------------------------------------------------------------------ */

[$output, $code] = nlb_test_run($temp . '/db/rollback.php', ['--yes', '--menus'], $temp);
nlb_test_assert($code === 0, 'откат: exit 0');
nlb_test_assert(str_contains($output, 'элементы 91'), 'откат: удалено 91 элементов');
nlb_test_assert(!is_file($temp . '/menu/top.menu.php'), 'откат: меню удалено');
nlb_test_assert(!is_file($temp . '/services/.left.menu.php'), 'откат: меню раздела удалено');

[$output, $code] = nlb_test_run($temp . '/db/check.php', [], $temp);
nlb_test_assert($code === 1, 'check.php после отката: exit 1 (ожидаемо)');

/* ------------------------------------------------------------------ */
/* 8. Уборка                                                          */
/* ------------------------------------------------------------------ */

foreach ($linked as $name => $isLink) {
    if ($isLink) {
        @unlink($temp . '/' . $name);
    }
}
nlb_test_rmdir($temp);
putenv('NLB_ROOT');
putenv('NLB_STUB_STATE');

echo str_repeat('-', 60) . "\n";

if ($failures) {
    echo 'Провалено проверок: ' . count($failures) . "\n";
    foreach ($failures as $failure) {
        echo "  x {$failure}\n";
    }
    exit(1);
}

echo 'Все проверки пройдены: ' . count($checks) . "\n";
exit(0);
