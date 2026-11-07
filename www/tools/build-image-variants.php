<?php
/**
 * Варианты изображений для адаптивного вывода: WebP, AVIF и JPG-ширины.
 *
 * Для каждого JPG в assets/images шаблона создаёт:
 *   <имя>.webp, <имя>.avif            — полноразмерные версии для <source>;
 *   <имя>-480.jpg/.webp/.avif         — мобильная ширина;
 *   <имя>-960.jpg/.webp/.avif         — планшет, если исходник шире;
 *   <имя>-1280.jpg/.webp/.avif        — широкий hero, если исходник шире.
 *
 * Оригинал остаётся fallback-ом и старшей шириной в srcset. Скрипт
 * идемпотентен: повторный запуск перезаписывает варианты, исходники не трогает.
 *
 * Запуск: php tools/build-image-variants.php
 * Требуется GD с поддержкой WebP и AVIF (проверяется на старте).
 */

$root = dirname(__DIR__);
$imagesDir = $root . '/local/templates/nlb_bootstrap/assets/images';

if (!is_dir($imagesDir)) {
    fwrite(STDERR, "Нет каталога: {$imagesDir}\n");
    exit(1);
}

foreach (['imagewebp' => 'WebP', 'imageavif' => 'AVIF'] as $function => $label) {
    if (!function_exists($function)) {
        fwrite(STDERR, "GD без поддержки {$label} ({$function}).\n");
        exit(1);
    }
}

$widths = [480, 960, 1280];
$report = [];

foreach (glob($imagesDir . '/*.jpg') ?: [] as $sourceFile) {
    $name = basename($sourceFile);
    if (preg_match('/-(480|960|1280)\.jpg$/', $name)) {
        continue; // уже вариант
    }

    $image = @imagecreatefromjpeg($sourceFile);
    if (!$image) {
        fwrite(STDERR, "Не удалось прочитать {$name}\n");
        continue;
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $base = preg_replace('/\.jpg$/i', '', $sourceFile);
    $created = [];

    $save = static function ($resource, string $path, string $format) use (&$created): void {
        $ok = match ($format) {
            'webp' => imagewebp($resource, $path, 78),
            'avif' => imageavif($resource, $path, 55, 6),
            default => imagejpeg($resource, $path, 82),
        };
        if ($ok) {
            $created[] = basename($path);
        }
    };

    $save($image, $base . '.webp', 'webp');
    $save($image, $base . '.avif', 'avif');

    foreach ($widths as $target) {
        if ($target >= $width) {
            continue;
        }
        $resized = imagescale($image, $target, -1, IMG_BICUBIC);
        if (!$resized) {
            continue;
        }
        $save($resized, $base . '-' . $target . '.jpg', 'jpg');
        $save($resized, $base . '-' . $target . '.webp', 'webp');
        $save($resized, $base . '-' . $target . '.avif', 'avif');
        imagedestroy($resized);
    }

    imagedestroy($image);

    $report[] = sprintf(
        '%-24s %4dx%-4d %2d файлов',
        $name,
        $width,
        $height,
        count($created)
    );
}

echo "Варианты изображений:\n";
foreach ($report as $line) {
    echo '  + ' . $line . "\n";
}
echo 'Итого исходников: ' . count($report) . "\n";
