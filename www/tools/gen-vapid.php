<?php
/**
 * Генератор ключей VAPID для Web Push.
 *
 * Запуск: php tools/gen-vapid.php
 *
 * Создаёт local/php_interface/include/nlb_push_keys.php (в git не попадает):
 * публичный ключ в base64url (его получает браузер) и приватный — для подписи
 * VAPID JWT. Повторный запуск перезаписывает файл: все браузеры, подписанные
 * на старый ключ, перестанут получать push и должны подписаться заново.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$target = $root . '/local/php_interface/include/nlb_push_keys.php';

if (!function_exists('openssl_pkey_new')) {
    fwrite(STDERR, "Нужно расширение openssl.\n");
    exit(1);
}

define('B_PROLOG_INCLUDED', true);
require $root . '/local/php_interface/include/nlb_push.php';

$keyArgs = [
    'curve_name' => 'prime256v1',
    'private_key_type' => OPENSSL_KEYTYPE_EC,
];
$opensslConfig = nlb_push_openssl_config();
if ($opensslConfig !== '') {
    $keyArgs['config'] = $opensslConfig;
}

$key = openssl_pkey_new($keyArgs);
if ($key === false) {
    fwrite(STDERR, "Не удалось создать ключ: " . (string)openssl_error_string() . "\n");
    exit(1);
}

$details = openssl_pkey_get_details($key);
if (!is_array($details) || !isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
    fwrite(STDERR, "OpenSSL не вернул координаты ключа.\n");
    exit(1);
}

$publicRaw = "\x04" . $details['ec']['x'] . $details['ec']['y'];
$privateRaw = $details['ec']['d'];

$encode = static function (string $raw): string {
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
};

$public = $encode($publicRaw);
$private = $encode($privateRaw);

$php = "<?php\n"
    . "/**\n"
    . " * Ключи VAPID для Web Push. Создан tools/gen-vapid.php.\n"
    . " *\n"
    . " * Файл в git не попадает: приватный ключ — секрет. Если его нет,\n"
    . " * push молча выключен, а бронь и письма работают как обычно.\n"
    . " */\n\n"
    . "return [\n"
    . "    'public' => '" . $public . "',\n"
    . "    'private' => '" . $private . "',\n"
    . "    'subject' => 'mailto:info@example.by',\n"
    . "];\n";

if (@file_put_contents($target, $php) === false) {
    fwrite(STDERR, "Не удалось записать {$target}\n");
    exit(1);
}

echo "Ключи VAPID созданы: local/php_interface/include/nlb_push_keys.php\n";
echo "Публичный ключ (для справки): {$public}\n";
echo "\n";
echo "Дальше: откройте /personal/ под администратором и включите уведомления.\n";
echo "Приватный ключ не публикуйте и не коммитьте.\n";
