<?php
/**
 * NLB Bootstrap — заголовки безопасности.
 *
 * Вызывается из local/php_interface/init.php, то есть до вывода страницы.
 * Часть заголовков обычно ставят на уровне nginx/Apache; здесь они дублируются,
 * чтобы защита работала и на shared-хостинге без доступа к конфигу сервера.
 *
 * Отключить CSP (например, если подключается внешняя аналитика):
 *     define('NLB_CSP', false);
 * в начале local/php_interface/init.php.
 *
 * Проверено только синтаксически: реальные ответы требуют живого ядра,
 * поэтому в отчёте это помечено как «не проверено».
 */

if (!function_exists('nlb_security_is_admin')) {
    function nlb_security_is_admin(): bool
    {
        if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
            return true;
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';

        return strpos($uri, '/bitrix/') === 0;
    }
}

if (!function_exists('nlb_security_https')) {
    function nlb_security_https(): bool
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }

        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
}

if (!function_exists('nlb_security_headers')) {
    function nlb_security_headers(): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        // Версию PHP наружу не показываем.
        header_remove('X-Powered-By');

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
        header('Cross-Origin-Resource-Policy: same-origin');

        if (nlb_security_https()) {
            header('Strict-Transport-Security: max-age=31536000');
        }

        // CSP для админки не ставим: панель Битрикса собирается из inline-скриптов
        // и подключаемых виджетов, и строгая политика ломает её без предупреждения.
        if (nlb_security_is_admin()) {
            return;
        }

        if (defined('NLB_CSP') && NLB_CSP === false) {
            return;
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "style-src 'self' 'unsafe-inline'",
            "script-src 'self' 'unsafe-inline'",
            "connect-src 'self'",
            "manifest-src 'self'",
            "worker-src 'self'",
        ]);

        header('Content-Security-Policy: ' . $csp);
    }
}
