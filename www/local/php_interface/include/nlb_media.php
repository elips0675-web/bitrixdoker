<?php
/**
 * NLB Bootstrap — адаптивные изображения: <picture>, WebP/AVIF и srcset.
 *
 * Варианты рядом с исходником готовит tools/build-image-variants.php:
 *   photo.webp / photo.avif            — полный размер;
 *   photo-480.jpg/.webp/.avif          — мобильная ширина;
 *   photo-960.* / photo-1280.*         — планшет и широкий экран.
 *
 * nlb_picture() выводит <picture> с AVIF и WebP, фолбэком JPG и srcset.
 * Если вариантов нет (например, картинка загружена в /upload/ через админку),
 * возвращается обычный <img> — поведение не меняется.
 *
 * Подключается из local/php_interface/init.php.
 */

if (!function_exists('nlb_media_escape')) {
    function nlb_media_escape(string $value): string
    {
        return function_exists('htmlspecialcharsbx')
            ? htmlspecialcharsbx($value)
            : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('nlb_media_root')) {
    function nlb_media_root(): string
    {
        if (defined('NLB_MEDIA_ROOT') && NLB_MEDIA_ROOT !== '') {
            return rtrim((string)NLB_MEDIA_ROOT, '/\\');
        }

        if (defined('DOCUMENT_ROOT') && DOCUMENT_ROOT !== '') {
            return rtrim((string)DOCUMENT_ROOT, '/\\');
        }

        $root = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');

        return $root !== '' ? rtrim($root, '/\\') : '';
    }
}

if (!function_exists('nlb_media_file')) {
    /**
     * Файловая система по URL картинки; '' — файла нет или путь внешний.
     */
    function nlb_media_file(string $src): string
    {
        if ($src === '' || preg_match('~^(https?:)?//~i', $src)) {
            return '';
        }

        $path = (string)parse_url($src, PHP_URL_PATH);
        $root = nlb_media_root();
        if ($path === '' || $root === '') {
            return '';
        }

        $file = $root . '/' . ltrim(rawurldecode($path), '/');

        return is_file($file) ? $file : '';
    }
}

if (!function_exists('nlb_media_variants')) {
    /**
     * Варианты исходника. Результат кэшируется на время запроса.
     *
     * [
     *   'full'   => ['width' => 1200, 'jpg' => '…', 'webp' => '…', 'avif' => '…'],
     *   'widths' => [['width' => 480, 'jpg' => '…', 'webp' => '…', 'avif' => '…'], …],
     * ]
     * Пустой массив — вариантов нет, нужен обычный <img>.
     */
    function nlb_media_variants(string $src): array
    {
        static $cache = [];

        if (array_key_exists($src, $cache)) {
            return $cache[$src];
        }

        $cache[$src] = [];

        if (!preg_match('~\.jpe?g$~i', $src)) {
            return $cache[$src];
        }

        $file = nlb_media_file($src);
        if ($file === '') {
            return $cache[$src];
        }

        $size = @getimagesize($file);
        if (!$size) {
            return $cache[$src];
        }

        $width = (int)$size[0];
        $cleanUrl = (string)preg_replace('~\?.*$~', '', $src);
        $stemUrl = (string)preg_replace('~\.jpe?g$~i', '', $cleanUrl);
        $stemFile = (string)preg_replace('~\.jpe?g$~i', '', $file);

        $pick = static function (string $suffix) use ($stemUrl, $stemFile): string {
            return is_file($stemFile . $suffix) ? $stemUrl . $suffix : '';
        };

        $full = [
            'width' => $width,
            'jpg'   => $cleanUrl,
            'webp'  => $pick('.webp'),
            'avif'  => $pick('.avif'),
        ];

        $widths = [];
        foreach ([480, 960, 1280] as $target) {
            if ($target >= $width || !is_file($stemFile . '-' . $target . '.jpg')) {
                continue;
            }
            $widths[] = [
                'width' => $target,
                'jpg'   => $stemUrl . '-' . $target . '.jpg',
                'webp'  => $pick('-' . $target . '.webp'),
                'avif'  => $pick('-' . $target . '.avif'),
            ];
        }

        if ($widths === [] && $full['webp'] === '' && $full['avif'] === '') {
            return $cache[$src];
        }

        return $cache[$src] = ['full' => $full, 'widths' => $widths];
    }
}

if (!function_exists('nlb_media_srcset')) {
    /**
     * Строка srcset из вариантов: «url 480w, url 960w, url 1200w».
     * $format: jpg | webp | avif; $prefix добавляется к каждому адресу.
     */
    function nlb_media_srcset(array $variants, string $format, string $prefix = ''): string
    {
        $parts = [];
        foreach ($variants['widths'] as $variant) {
            if (($variant[$format] ?? '') !== '') {
                $parts[] = $prefix . $variant[$format] . ' ' . $variant['width'] . 'w';
            }
        }

        $full = $variants['full'];
        if (($full[$format] ?? '') !== '') {
            $parts[] = $prefix . $full[$format] . ' ' . $full['width'] . 'w';
        }

        return implode(', ', $parts);
    }
}

if (!function_exists('nlb_picture')) {
    /**
     * <picture> с AVIF/WebP и JPG-фолбэком.
     *
     * $options:
     *   class    — класс на <img>;
     *   sizes    — атрибут sizes (по умолчанию 100vw);
     *   loading  — lazy (по умолчанию) или eager;
     *   decoding — async (по умолчанию);
     *   priority — true для LCP-картинки: loading=eager + fetchpriority=high.
     */
    function nlb_picture(string $src, string $alt = '', array $options = []): string
    {
        if ($src === '') {
            return '';
        }

        $class = (string)($options['class'] ?? '');
        $sizes = (string)($options['sizes'] ?? '100vw');
        $loading = (string)($options['loading'] ?? 'lazy');
        $decoding = (string)($options['decoding'] ?? 'async');
        $prefix = (string)($options['prefix'] ?? '');
        $priority = !empty($options['priority']);

        if ($priority) {
            $loading = 'eager';
        }

        $attrs = '';
        if ($class !== '') {
            $attrs .= ' class="' . nlb_media_escape($class) . '"';
        }
        $attrs .= ' src="' . nlb_media_escape($prefix . $src) . '"';
        $attrs .= ' alt="' . nlb_media_escape($alt) . '"';
        $attrs .= ' loading="' . nlb_media_escape($loading) . '"';
        $attrs .= ' decoding="' . nlb_media_escape($decoding) . '"';
        if ($priority) {
            $attrs .= ' fetchpriority="high"';
        }

        $variants = nlb_media_variants($src);
        if ($variants === []) {
            return '<img' . $attrs . '>';
        }

        $sizesAttribute = $sizes !== '' ? ' sizes="' . nlb_media_escape($sizes) . '"' : '';
        $jpegSrcset = nlb_media_srcset($variants, 'jpg', $prefix);
        if ($jpegSrcset !== '') {
            $attrs .= ' srcset="' . nlb_media_escape($jpegSrcset) . '"';
        }

        $sources = '';
        foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $format => $type) {
            $srcset = nlb_media_srcset($variants, $format, $prefix);
            if ($srcset === '') {
                continue;
            }
            $sources .= '<source type="' . $type . '" srcset="' . nlb_media_escape($srcset) . '"'
                . $sizesAttribute . '>' . "\n";
        }

        return '<picture class="nlb-picture">' . "\n"
            . $sources
            . '<img' . $attrs . $sizesAttribute . '>' . "\n"
            . '</picture>';
    }
}
