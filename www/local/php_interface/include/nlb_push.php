<?php
/**
 * NLB Bootstrap — Web Push (VAPID) для уведомлений о бронировании.
 *
 * Что здесь есть:
 *  - чтение ключей VAPID из `local/php_interface/include/nlb_push_keys.php`
 *    (файл создаёт `php tools/gen-vapid.php`, в git не попадает) или из
 *    переменных окружения NLB_VAPID_PUBLIC / NLB_VAPID_PRIVATE / NLB_VAPID_SUBJECT;
 *  - хранение подписок браузеров в опции `nlb_bootstrap.push_subscriptions`;
 *  - отправка push по протоколу Web Push: VAPID JWT (ES256) + aes128gcm
 *    (RFC 8291), без composer и внешних библиотек;
 *  - уведомление сотрудников о новой брони.
 *
 * Без ключей модуль молча выключен: бронь, письмо и сайт работают как раньше.
 * Отправка идёт только по HTTPS-адресам push-сервисов; сеть проверяется
 * по HTTP-коду (201 — доставлено, 404/410 — подписка мертва и удаляется).
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!function_exists('nlb_push_config')) {
    /**
     * Настройки VAPID: публичный ключ, приватный ключ, subject, TTL.
     *
     * @return array{public:string, private:string, subject:string, ttl:int}
     */
    function nlb_push_config(): array
    {
        static $config = null;
        if (is_array($config)) {
            return $config;
        }

        $keys = [];
        $file = __DIR__ . '/nlb_push_keys.php';
        if (is_file($file)) {
            $loaded = require $file;
            if (is_array($loaded)) {
                $keys = $loaded;
            }
        }

        $public = trim((string)($keys['public'] ?? ''));
        if ($public === '') {
            $public = trim((string)getenv('NLB_VAPID_PUBLIC'));
        }

        $private = trim((string)($keys['private'] ?? ''));
        if ($private === '') {
            $private = trim((string)getenv('NLB_VAPID_PRIVATE'));
        }

        $subject = trim((string)($keys['subject'] ?? ''));
        if ($subject === '') {
            $subject = trim((string)getenv('NLB_VAPID_SUBJECT'));
        }
        if ($subject === '') {
            $subject = 'mailto:info@example.by';
        }

        $config = [
            'public' => $public,
            'private' => $private,
            'subject' => $subject,
            'ttl' => 86400,
        ];

        return $config;
    }
}

if (!function_exists('nlb_push_enabled')) {
    function nlb_push_enabled(): bool
    {
        $config = nlb_push_config();

        return $config['public'] !== '' && $config['private'] !== '';
    }
}

if (!function_exists('nlb_push_public_key')) {
    /**
     * Публичный ключ для `applicationServerKey` в браузере.
     */
    function nlb_push_public_key(): string
    {
        $config = nlb_push_config();

        return nlb_push_enabled() ? $config['public'] : '';
    }
}

if (!function_exists('nlb_push_subscriptions')) {
    /**
     * Подписки браузеров. Хранятся одной JSON-опцией: устройств у библиотеки
     * немного, а отдельная таблица потребовала бы миграции схемы.
     *
     * @return array<int,array{endpoint:string,p256dh:string,auth:string,role:string,email:string,ua:string,created:int}>
     */
    function nlb_push_subscriptions(): array
    {
        if (!class_exists('COption')) {
            return [];
        }

        $raw = (string)COption::GetOptionString('nlb_bootstrap', 'push_subscriptions', '[]');
        $list = json_decode($raw, true);
        if (!is_array($list)) {
            return [];
        }

        $out = [];
        foreach ($list as $row) {
            if (!is_array($row)) {
                continue;
            }
            $endpoint = trim((string)($row['endpoint'] ?? ''));
            if ($endpoint === '') {
                continue;
            }
            $out[] = [
                'endpoint' => $endpoint,
                'p256dh' => (string)($row['p256dh'] ?? ''),
                'auth' => (string)($row['auth'] ?? ''),
                'role' => (string)($row['role'] ?? 'client'),
                'email' => (string)($row['email'] ?? ''),
                'ua' => (string)($row['ua'] ?? ''),
                'created' => (int)($row['created'] ?? 0),
            ];
        }

        return $out;
    }
}

if (!function_exists('nlb_push_save_subscriptions')) {
    function nlb_push_save_subscriptions(array $list): void
    {
        if (!class_exists('COption')) {
            return;
        }

        $list = array_slice(array_values($list), 0, 200);
        COption::SetOptionString(
            'nlb_bootstrap',
            'push_subscriptions',
            (string)json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}

if (!function_exists('nlb_push_subscribe')) {
    /**
     * Сохранить подписку браузера. Повторная подписка с тем же endpoint
     * обновляет ключи и роль, а не плодит дубли.
     *
     * @param array<string,mixed> $subscription объект PushSubscription.toJSON()
     */
    function nlb_push_subscribe(array $subscription, string $role = 'client', string $email = ''): bool
    {
        $endpoint = trim((string)($subscription['endpoint'] ?? ''));
        $keys = is_array($subscription['keys'] ?? null) ? $subscription['keys'] : [];
        $p256dh = trim((string)($subscription['p256dh'] ?? $keys['p256dh'] ?? ''));
        $auth = trim((string)($subscription['auth'] ?? $keys['auth'] ?? ''));

        if (!preg_match('#^https://#i', $endpoint) || strlen($endpoint) > 1000) {
            return false;
        }
        if (strlen(nlb_push_b64url_decode($p256dh)) !== 65) {
            return false;
        }
        if (strlen(nlb_push_b64url_decode($auth)) < 16) {
            return false;
        }

        $role = $role === 'staff' ? 'staff' : 'client';
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = '';
        }

        $ua = '';
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 180);
        }

        $list = nlb_push_subscriptions();
        $found = false;
        foreach ($list as $index => $row) {
            if ($row['endpoint'] !== $endpoint) {
                continue;
            }
            $list[$index]['p256dh'] = $p256dh;
            $list[$index]['auth'] = $auth;
            $list[$index]['role'] = $role;
            $list[$index]['email'] = $email;
            $list[$index]['ua'] = $ua;
            $found = true;
            break;
        }

        if (!$found) {
            $list[] = [
                'endpoint' => $endpoint,
                'p256dh' => $p256dh,
                'auth' => $auth,
                'role' => $role,
                'email' => $email,
                'ua' => $ua,
                'created' => time(),
            ];
        }

        nlb_push_save_subscriptions($list);

        return true;
    }
}

if (!function_exists('nlb_push_unsubscribe')) {
    function nlb_push_unsubscribe(string $endpoint): bool
    {
        $endpoint = trim($endpoint);
        if ($endpoint === '') {
            return false;
        }

        $list = nlb_push_subscriptions();
        $left = [];
        $removed = false;
        foreach ($list as $row) {
            if ($row['endpoint'] === $endpoint) {
                $removed = true;
                continue;
            }
            $left[] = $row;
        }

        if ($removed) {
            nlb_push_save_subscriptions($left);
        }

        return $removed;
    }
}

if (!function_exists('nlb_push_payload_booking')) {
    /**
     * Текст push-уведомления о новой брони.
     *
     * @param array<string,mixed> $values поля заявки (SERVICE, DATE_HUMAN, TIME, PERSON, PHONE)
     * @return array{title:string,body:string,url:string,tag:string,icon:string}
     */
    function nlb_push_payload_booking(array $values, int $elementId = 0): array
    {
        $service = trim((string)($values['SERVICE'] ?? ''));
        if ($service === '') {
            $service = 'Услуга';
        }

        $date = trim((string)($values['DATE_HUMAN'] ?? $values['DATE'] ?? ''));
        $time = trim((string)($values['TIME'] ?? ''));
        $person = trim((string)($values['PERSON'] ?? ''));
        $phone = trim((string)($values['PHONE'] ?? ''));

        $body = $date;
        if ($time !== '') {
            $body .= ($body !== '' ? ', ' : '') . $time;
        }
        if ($person !== '') {
            $body .= ' — ' . $person;
        }
        if ($phone !== '') {
            $body .= ', ' . $phone;
        }

        return [
            'title' => 'Новая бронь: ' . $service,
            'body' => $body !== '' ? $body : 'Заявка на бронирование',
            'url' => '/',
            'tag' => 'booking-' . ($elementId > 0 ? $elementId : time()),
            'icon' => '/local/templates/nlb_bootstrap/assets/icons/icon-192.png',
        ];
    }
}

if (!function_exists('nlb_push_notify_booking')) {
    /**
     * Уведомить сотрудников о новой брони. Ошибки сети не должны ломать
     * сохранение заявки, поэтому всё обёрнуто в try/catch.
     *
     * @param array<string,mixed> $values
     */
    function nlb_push_notify_booking(array $values, string $url = '', int $elementId = 0): void
    {
        if (!nlb_push_enabled()) {
            return;
        }

        $payload = nlb_push_payload_booking($values, $elementId);
        if ($url !== '' && str_starts_with($url, '/')) {
            $payload['url'] = $url;
        }

        try {
            nlb_push_broadcast($payload, 'staff');
        } catch (Throwable $e) {
            /* Push — уведомление, а не часть записи. */
        }
    }
}

if (!function_exists('nlb_push_broadcast')) {
    /**
     * Отправить push всем подпискам роли (по умолчанию сотрудникам).
     * Мёртвые подписки (404/410) удаляются из хранилища.
     *
     * @param array<string,mixed> $payload
     * @return array{sent:int,gone:int,failed:int}
     */
    function nlb_push_broadcast(array $payload, string $role = 'staff'): array
    {
        $result = ['sent' => 0, 'gone' => 0, 'failed' => 0];
        $list = nlb_push_subscriptions();
        $alive = [];

        foreach ($list as $subscription) {
            if ($role !== '' && $subscription['role'] !== $role) {
                $alive[] = $subscription;
                continue;
            }

            $status = nlb_push_send($subscription, $payload);
            if ($status === 'gone') {
                $result['gone']++;
                continue;
            }

            $alive[] = $subscription;
            if ($status === 'ok') {
                $result['sent']++;
            } else {
                $result['failed']++;
            }
        }

        if ($result['gone'] > 0) {
            nlb_push_save_subscriptions($alive);
        }

        return $result;
    }
}

if (!function_exists('nlb_push_send')) {
    /**
     * Отправить одно уведомление.
     *
     * @param array<string,mixed> $subscription
     * @param array<string,mixed> $payload
     * @return string ok|gone|error|disabled
     */
    function nlb_push_send(array $subscription, array $payload): string
    {
        $config = nlb_push_config();
        if ($config['public'] === '' || $config['private'] === '') {
            return 'disabled';
        }

        $endpoint = (string)($subscription['endpoint'] ?? '');
        $uaPublic = nlb_push_b64url_decode((string)($subscription['p256dh'] ?? ''));
        $auth = nlb_push_b64url_decode((string)($subscription['auth'] ?? ''));

        if ($endpoint === '' || strlen($uaPublic) !== 65 || strlen($auth) < 16) {
            return 'error';
        }

        $json = (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $encrypted = nlb_push_encrypt($json, $uaPublic, substr($auth, 0, 16));
        if ($encrypted === null) {
            return 'error';
        }

        $token = nlb_push_vapid_token($endpoint, $config);
        if ($token === '') {
            return 'error';
        }

        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'TTL: ' . (int)$config['ttl'],
            'Urgency: normal',
            'Authorization: vapid t=' . $token . ', k=' . $config['public'],
        ];

        $code = nlb_push_http_post($endpoint, $encrypted, $headers);
        if ($code >= 200 && $code < 300) {
            return 'ok';
        }
        if ($code === 404 || $code === 410) {
            return 'gone';
        }

        return 'error';
    }
}

if (!function_exists('nlb_push_openssl_config')) {
    /**
     * Путь к openssl.cnf. На Windows-сборках PHP (Laragon) его нет в PATH,
     * и без файла openssl_pkey_new возвращает false с «no such file».
     */
    function nlb_push_openssl_config(): string
    {
        static $path = null;
        if (is_string($path)) {
            return $path;
        }

        $candidates = [];
        $env = getenv('OPENSSL_CONF');
        if (is_string($env) && $env !== '') {
            $candidates[] = $env;
        }
        if (defined('PHP_BINARY') && PHP_BINARY !== '') {
            $dir = dirname(PHP_BINARY);
            $candidates[] = $dir . '/extras/ssl/openssl.cnf';
            $candidates[] = $dir . '/openssl.cnf';
            $candidates[] = $dir . '/extras/openssl.cnf';
        }
        $candidates[] = 'C:/Program Files/Common Files/SSL/openssl.cnf';
        $candidates[] = '/etc/ssl/openssl.cnf';
        $candidates[] = '/usr/local/ssl/openssl.cnf';

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $path = $candidate;

                return $path;
            }
        }

        $path = '';

        return $path;
    }
}

if (!function_exists('nlb_push_http_post')) {
    /**
     * POST в push-сервис. curl есть почти всегда; поток — запасной путь.
     *
     * @param array<int,string> $headers
     */
    function nlb_push_http_post(string $url, string $body, array $headers): int
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl !== false) {
                curl_setopt_array($curl, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $body,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => false,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_CONNECTTIMEOUT => 5,
                ]);
                $response = curl_exec($curl);
                $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                if ($response !== false || $code > 0) {
                    return $code;
                }
            }
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if (function_exists('http_get_last_response_headers')) {
            $responseHeaders = http_get_last_response_headers();
        } else {
            $responseHeaders = $http_response_header ?? [];
        }
        if (isset($responseHeaders[0])
            && preg_match('/\s(\d{3})\s/', (string)$responseHeaders[0], $match)
        ) {
            return (int)$match[1];
        }

        return $response === false ? 0 : 200;
    }
}

if (!function_exists('nlb_push_b64url_encode')) {
    function nlb_push_b64url_encode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}

if (!function_exists('nlb_push_b64url_decode')) {
    function nlb_push_b64url_decode(string $value): string
    {
        $value = strtr(trim($value), '-_', '+/');
        $pad = strlen($value) % 4;
        if ($pad > 0) {
            $value .= str_repeat('=', 4 - $pad);
        }

        $raw = base64_decode($value, true);

        return $raw === false ? '' : $raw;
    }
}

if (!function_exists('nlb_push_hkdf_expand')) {
    /**
     * HKDF-Expand (RFC 5869) на HMAC-SHA-256: для Web Push хватает одного блока.
     */
    function nlb_push_hkdf_expand(string $prk, string $info, int $length): string
    {
        $out = '';
        $block = '';
        $counter = 1;
        while (strlen($out) < $length) {
            $block = hash_hmac('sha256', $block . $info . chr($counter), $prk, true);
            $out .= $block;
            $counter++;
        }

        return substr($out, 0, $length);
    }
}

if (!function_exists('nlb_push_public_pem')) {
    /**
     * Несжатая точка P-256 (65 байт, 0x04 || X || Y) → PEM SubjectPublicKeyInfo.
     */
    function nlb_push_public_pem(string $raw): string
    {
        if (strlen($raw) !== 65 || $raw[0] !== "\x04") {
            return '';
        }

        $prefix = (string)hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200');
        $der = $prefix . $raw;

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }
}

if (!function_exists('nlb_push_private_pem')) {
    /**
     * Приватный ключ: PEM как есть или 32-байтная скалярная часть в base64url.
     */
    function nlb_push_private_pem(string $value): string
    {
        if (strpos($value, 'BEGIN') !== false) {
            return $value;
        }

        $raw = nlb_push_b64url_decode($value);
        if (strlen($raw) !== 32) {
            return '';
        }

        $der = (string)hex2bin('30310201010420') . $raw . (string)hex2bin('a00a06082a8648ce3d030107');

        return "-----BEGIN EC PRIVATE KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END EC PRIVATE KEY-----\n";
    }
}

if (!function_exists('nlb_push_der_to_raw')) {
    /**
     * DER-подпись ECDSA (два INTEGER) → 64 байта r || s.
     */
    function nlb_push_der_to_raw(string $der): string
    {
        $readInt = static function (string $data, int &$pos): string {
            if (($data[$pos] ?? '') !== "\x02" || !isset($data[$pos + 1])) {
                return '';
            }
            $length = ord($data[$pos + 1]);
            $pos += 2;
            if (($length & 0x80) !== 0) {
                $count = $length & 0x7f;
                $length = 0;
                for ($i = 0; $i < $count; $i++) {
                    $length = ($length << 8) | ord($data[$pos + $i] ?? "\x00");
                }
                $pos += $count;
            }
            $value = substr($data, $pos, $length);
            $pos += $length;

            return str_pad(ltrim($value, "\x00"), 32, "\x00", STR_PAD_LEFT);
        };

        if (($der[0] ?? '') !== "\x30") {
            return '';
        }
        $pos = 2;
        if ((ord($der[1]) & 0x80) !== 0) {
            $pos = 2 + (ord($der[1]) & 0x7f);
        }

        $r = $readInt($der, $pos);
        $s = $readInt($der, $pos);
        if (strlen($r) !== 32 || strlen($s) !== 32) {
            return '';
        }

        return $r . $s;
    }
}

if (!function_exists('nlb_push_raw_to_der')) {
    /**
     * 64 байта r || s → DER-подпись (нужно самопроверке и отладке).
     */
    function nlb_push_raw_to_der(string $raw): string
    {
        if (strlen($raw) !== 64) {
            return '';
        }

        $int = static function (string $part): string {
            $part = ltrim($part, "\x00");
            if ($part === '' || (ord($part[0]) & 0x80) !== 0) {
                $part = "\x00" . $part;
            }

            return "\x02" . chr(strlen($part)) . $part;
        };

        $body = $int(substr($raw, 0, 32)) . $int(substr($raw, 32, 32));

        return "\x30" . chr(strlen($body)) . $body;
    }
}

if (!function_exists('nlb_push_encrypt')) {
    /**
     * Шифрование payload по RFC 8291 (Content-Encoding: aes128gcm).
     *
     * @return string|null тело запроса: salt(16) || rs(4) || idlen(1) || keyid(65) || ciphertext || tag
     */
    function nlb_push_encrypt(string $payload, string $uaPublicRaw, string $authSecret): ?string
    {
        if (!function_exists('openssl_pkey_new') || !function_exists('openssl_pkey_derive')) {
            return null;
        }

        $args = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];
        $opensslConfig = nlb_push_openssl_config();
        if ($opensslConfig !== '') {
            $args['config'] = $opensslConfig;
        }

        $key = openssl_pkey_new($args);
        if ($key === false) {
            return null;
        }

        $details = openssl_pkey_get_details($key);
        if (!is_array($details) || !isset($details['ec']['x'], $details['ec']['y'])) {
            return null;
        }

        $asPublic = "\x04" . $details['ec']['x'] . $details['ec']['y'];
        $pem = nlb_push_public_pem($uaPublicRaw);
        if ($pem === '') {
            return null;
        }

        $uaKey = openssl_pkey_get_public($pem);
        if ($uaKey === false) {
            return null;
        }

        $shared = openssl_pkey_derive($uaKey, $key);
        if (!is_string($shared) || $shared === '') {
            return null;
        }

        $prkKey = hash_hmac('sha256', $shared, $authSecret, true);
        $ikm = nlb_push_hkdf_expand($prkKey, "WebPush: info\x00" . $uaPublicRaw . $asPublic, 32);
        $salt = random_bytes(16);
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $cek = nlb_push_hkdf_expand($prk, "Content-Encoding: aes128gcm\x00", 16);
        $nonce = nlb_push_hkdf_expand($prk, "Content-Encoding: nonce\x00", 12);

        $tag = '';
        $cipher = openssl_encrypt(
            $payload . "\x02",
            'aes-128-gcm',
            $cek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );
        if ($cipher === false) {
            return null;
        }

        return $salt . pack('N', 4096) . chr(strlen($asPublic)) . $asPublic . $cipher . $tag;
    }
}

if (!function_exists('nlb_push_vapid_token')) {
    /**
     * VAPID JWT (ES256) для заголовка Authorization.
     *
     * @param array{public:string, private:string, subject:string, ttl:int} $config
     */
    function nlb_push_vapid_token(string $endpoint, array $config): string
    {
        $parts = parse_url($endpoint);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $audience = $parts['scheme'] . '://' . $parts['host'];
        $header = nlb_push_b64url_encode('{"typ":"JWT","alg":"ES256"}');
        $claims = nlb_push_b64url_encode((string)json_encode([
            'aud' => $audience,
            'exp' => time() + 12 * 3600,
            'sub' => (string)$config['subject'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $input = $header . '.' . $claims;
        $pem = nlb_push_private_pem((string)$config['private']);
        if ($pem === '') {
            return '';
        }

        $der = '';
        if (!openssl_sign($input, $der, $pem, OPENSSL_ALGO_SHA256)) {
            return '';
        }

        $raw = nlb_push_der_to_raw($der);
        if ($raw === '') {
            return '';
        }

        return $input . '.' . nlb_push_b64url_encode($raw);
    }
}
