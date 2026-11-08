<?php
/**
 * Факты о стенде для гейта документации.
 *
 * Считает то, о чём пишут README и бэклог: конфиг инфоблоков, демо-данные,
 * миграции, сервисы и порты compose, версии PHP и MySQL. Источник — код и
 * каталоги репозитория, а не текст документов: число в README не доказывает
 * само себя.
 *
 * Запуск: php tools/stand-facts.php
 *         php tools/stand-facts.php --json   — один объект для гейта
 * Выход:  0 — посчитано, 1 — не хватает файла или каталога.
 */

$root = dirname(__DIR__);
$www = $root . '/www';
$errors = [];

$configPath = $www . '/db/config/iblocks.php';
$config = [];
if (is_file($configPath)) {
    $config = require $configPath;
    if (!is_array($config)) {
        $errors[] = 'www/db/config/iblocks.php: не вернул массив';
        $config = [];
    }
} else {
    $errors[] = 'нет файла: www/db/config/iblocks.php';
}

$iblocks = $config['iblocks'] ?? [];
$properties = 0;
$sections = 0;
foreach ($iblocks as $iblock) {
    $properties += count($iblock['properties'] ?? []);
    $sections += count($iblock['sections_data'] ?? []);
}

$elements = 0;
$dataFiles = glob($www . '/db/config/data/*.php') ?: [];
if ($dataFiles === []) {
    $errors[] = 'нет файлов демо-контента: www/db/config/data/*.php';
}
foreach ($dataFiles as $file) {
    $data = require $file;
    if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
        $errors[] = 'data/' . basename($file) . ': нет ключа items';
        continue;
    }
    $elements += count($data['items']);
}

$migrations = glob($www . '/db/migrations/*.php') ?: [];
if ($migrations === []) {
    $errors[] = 'нет миграций: www/db/migrations/*.php';
}

$composePath = $root . '/docker-compose.yml';
$compose = is_file($composePath) ? (string)file_get_contents($composePath) : '';
if ($compose === '') {
    $errors[] = 'нет файла: docker-compose.yml';
}

$services = 0;
$inServices = false;
foreach (preg_split('/\R/', $compose) as $line) {
    if (preg_match('/^services:\s*$/', $line)) {
        $inServices = true;
        continue;
    }
    if ($inServices && preg_match('/^\S/', $line)) {
        break;
    }
    if ($inServices && preg_match('/^  ([A-Za-z0-9_-]+):\s*$/', $line)) {
        $services++;
    }
}

$portWeb = 0;
$portDb = 0;
if (preg_match('/"(\d{2,5}):80"/', $compose, $match)) {
    $portWeb = (int)$match[1];
}
if (preg_match('/"(\d{2,5}):3306"/', $compose, $match)) {
    $portDb = (int)$match[1];
}

$mysqlVersion = '';
if (preg_match('/image:\s*mysql:(\d+\.\d+)/', $compose, $match)) {
    $mysqlVersion = $match[1];
}

$dockerfile = $root . '/php/Dockerfile';
$phpVersion = '';
if (is_file($dockerfile)) {
    if (preg_match('/^FROM\s+php:(\d+\.\d+)/mi', (string)file_get_contents($dockerfile), $match)) {
        $phpVersion = $match[1];
    } else {
        $errors[] = 'php/Dockerfile: нет строки FROM php:<версия>';
    }
} else {
    $errors[] = 'нет файла: php/Dockerfile';
}

$facts = [
    'types' => count($config['types'] ?? []),
    'iblocks' => count($iblocks),
    'properties' => $properties,
    'sections' => $sections,
    'elements' => $elements,
    'migrations' => count($migrations),
    'services' => $services,
    'port_web' => $portWeb,
    'port_db' => $portDb,
    'mysql_version' => $mysqlVersion,
    'php_version' => $phpVersion,
];

if ($errors !== []) {
    fwrite(STDERR, "stand-facts.php: факты не посчитаны\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '  - ' . $error . "\n");
    }
    exit(1);
}

if (in_array('--json', $argv, true)) {
    echo json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

foreach ($facts as $key => $value) {
    echo $key . ': ' . $value . "\n";
}
exit(0);
