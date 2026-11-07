<?php
/**
 * Самопроверка Web Push без Битрикса и без сети.
 *
 * Запуск: php tools/push-selftest.php
 *
 * Что проверяется:
 *  - base64url туда и обратно;
 *  - шифрование payload (RFC 8291): результат расшифровывается ключом
 *    «браузера», который участвовал в обмене;
 *  - подпись VAPID JWT (ES256) проверяется публичным ключом;
 *  - текст push о брони собирается из полей заявки.
 *
 * Это не проверка доставки: push-сервис (FCM/Mozilla) в тесте не участвует.
 */

declare(strict_types=1);

define('B_PROLOG_INCLUDED', true);

require dirname(__DIR__) . '/local/php_interface/include/nlb_push.php';

$failures = 0;
$checks = 0;

$check = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if ($condition) {
        echo "  + {$message}\n";
        return;
    }
    $failures++;
    echo "  x {$message}\n";
};

$encode = static function (string $raw): string {
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
};

echo "Web Push: самопроверка\n\n";

/* 1. base64url */
$blob = random_bytes(65);
$check(nlb_push_b64url_decode(nlb_push_b64url_encode($blob)) === $blob, 'base64url: туда и обратно');
$check(nlb_push_b64url_decode('') === '', 'base64url: пустая строка');
$check(nlb_push_b64url_decode('***') === '', 'base64url: мусор не декодируется');

/* 2. Ключи «браузера» */
$keyArgs = [
    'curve_name' => 'prime256v1',
    'private_key_type' => OPENSSL_KEYTYPE_EC,
];
$opensslConfig = nlb_push_openssl_config();
if ($opensslConfig !== '') {
    $keyArgs['config'] = $opensslConfig;
}

$uaKey = openssl_pkey_new($keyArgs);
$uaDetails = openssl_pkey_get_details($uaKey);
$uaPublic = "\x04" . $uaDetails['ec']['x'] . $uaDetails['ec']['y'];
$authSecret = random_bytes(16);

/* 3. Шифрование и расшифровка */
$payload = json_encode([
    'title' => 'Новая бронь: Спортзал',
    'body' => '06.11.2026, 14:00 — Иван, +375 (29) 000-00-00',
    'url' => '/services/gym/#booking',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$body = nlb_push_encrypt((string)$payload, $uaPublic, $authSecret);
$check(is_string($body) && strlen($body) > 100, 'шифрование: тело собрано');

if (is_string($body)) {
    $salt = substr($body, 0, 16);
    $rs = unpack('N', substr($body, 16, 4));
    $idLength = ord(substr($body, 20, 1));
    $keyId = substr($body, 21, $idLength);
    $cipher = substr($body, 21 + $idLength, -16);
    $tag = substr($body, -16);

    $check((int)($rs[1] ?? 0) === 4096, 'шифрование: rs = 4096');
    $check($idLength === 65 && strlen($keyId) === 65, 'шифрование: ключ сервера 65 байт');

    $shared = openssl_pkey_derive(openssl_pkey_get_public(nlb_push_public_pem($keyId)), $uaKey);
    $prkKey = hash_hmac('sha256', (string)$shared, $authSecret, true);
    $ikm = nlb_push_hkdf_expand($prkKey, "WebPush: info\x00" . $uaPublic . $keyId, 32);
    $prk = hash_hmac('sha256', $ikm, $salt, true);
    $cek = nlb_push_hkdf_expand($prk, "Content-Encoding: aes128gcm\x00", 16);
    $nonce = nlb_push_hkdf_expand($prk, "Content-Encoding: nonce\x00", 12);

    $plain = openssl_decrypt($cipher, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
    $check(is_string($plain), 'шифрование: расшифровка прошла');
    if (is_string($plain)) {
        $plain = rtrim($plain, "\x00");
        if (str_ends_with($plain, "\x02")) {
            $plain = substr($plain, 0, -1);
        }
        $check($plain === $payload, 'шифрование: payload совпал побайтово');
    }
}

/* 4. VAPID JWT */
$vapidKey = openssl_pkey_new($keyArgs);
$vapidDetails = openssl_pkey_get_details($vapidKey);
$vapidPrivate = $encode($vapidDetails['ec']['d']);
$vapidPublicRaw = "\x04" . $vapidDetails['ec']['x'] . $vapidDetails['ec']['y'];

$config = [
    'public' => $encode($vapidPublicRaw),
    'private' => $vapidPrivate,
    'subject' => 'mailto:info@example.by',
    'ttl' => 86400,
];

$token = nlb_push_vapid_token('https://fcm.googleapis.com/fcm/send/test-endpoint', $config);
$parts = explode('.', $token);
$check(count($parts) === 3, 'VAPID: токен из трёх частей');

if (count($parts) === 3) {
    $header = json_decode((string)nlb_push_b64url_decode($parts[0]), true);
    $claims = json_decode((string)nlb_push_b64url_decode($parts[1]), true);
    $signature = nlb_push_raw_to_der(nlb_push_b64url_decode($parts[2]));

    $check(($header['alg'] ?? '') === 'ES256', 'VAPID: alg = ES256');
    $check(($claims['aud'] ?? '') === 'https://fcm.googleapis.com', 'VAPID: aud = origin сервиса');
    $check((int)($claims['exp'] ?? 0) > time(), 'VAPID: срок действия в будущем');
    $check(($claims['sub'] ?? '') === 'mailto:info@example.by', 'VAPID: subject на месте');

    $verified = openssl_verify(
        $parts[0] . '.' . $parts[1],
        $signature,
        nlb_push_public_pem($vapidPublicRaw),
        OPENSSL_ALGO_SHA256
    );
    $check($verified === 1, 'VAPID: подпись проверена публичным ключом');
}

/* 5. Текст уведомления о брони */
$payloadBooking = nlb_push_payload_booking([
    'SERVICE' => 'Спортзал',
    'DATE_HUMAN' => '06.11.2026',
    'TIME' => '14:00',
    'PERSON' => 'Иван',
    'PHONE' => '+375 (29) 000-00-00',
], 42);

$check($payloadBooking['title'] === 'Новая бронь: Спортзал', 'бронь: заголовок');
$check($payloadBooking['body'] === '06.11.2026, 14:00 — Иван, +375 (29) 000-00-00', 'бронь: тело');
$check($payloadBooking['tag'] === 'booking-42', 'бронь: tag по ID элемента');
$check(str_starts_with($payloadBooking['icon'], '/local/templates/nlb_bootstrap/'), 'бронь: иконка шаблона');

/* 6. Невалидная подписка не сохраняется */
$check(!nlb_push_subscribe(['endpoint' => 'http://example.com/x', 'keys' => []], 'staff'), 'подписка: http отклонён');
$check(!nlb_push_subscribe(['endpoint' => 'https://example.com/x', 'keys' => ['p256dh' => 'abc', 'auth' => 'abc']], 'staff'), 'подписка: короткие ключи отклонены');

echo "\n";
if ($failures > 0) {
    echo "Провалено проверок: {$failures} из {$checks}.\n";
    exit(1);
}

echo "Все проверки пройдены ({$checks}).\n";
exit(0);
