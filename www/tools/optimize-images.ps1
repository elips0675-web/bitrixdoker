# Пережатие демо-изображений шаблона nlb_bootstrap.
#
# Что делает:
#  1) кадрирует по центру под пропорции, в которых картинка реально выводится
#     (CSS использует object-fit: cover, поэтому лишние края всё равно обрезались);
#  2) уменьшает до разумной ширины (больше 2x от слота не нужно);
#  3) подбирает качество JPEG под целевой вес.
#
# Файл никогда не увеличивается: если новый вариант больше исходного, остаётся исходный.
#
# Запуск: powershell -NoProfile -ExecutionPolicy Bypass -File tools\optimize-images.ps1
#
# После пережатия запустите php tools\build-image-variants.php — он соберёт
# WebP, AVIF и ширины 480/960/1280 для <picture> и srcset. System.Drawing
# не умеет WebP/AVIF, поэтому это отдельный PHP-скрипт на GD.
#
# Требуется Windows PowerShell 5.1+ и System.Drawing (.NET Framework).

Add-Type -AssemblyName System.Drawing

$dir = Join-Path $PSScriptRoot '..\local\templates\nlb_bootstrap\assets\images'
$dir = (Resolve-Path $dir).Path

# Пропорции вывода (ширина / высота) и целевой вес в килобайтах.
# hero — широкий баннер, карточки новостей и событий — 3:2, печать — 4:3.
$settings = @{
    'hero-1.jpg'          = @{ Aspect = 16 / 9; MaxWidth = 1400; TargetKb = 250 }
    'hero-2.jpg'          = @{ Aspect = 16 / 9; MaxWidth = 1400; TargetKb = 220 }
    'hero-3.jpg'          = @{ Aspect = 16 / 9; MaxWidth = 1400; TargetKb = 180 }
    'event.jpg'           = @{ Aspect = 3 / 2;  MaxWidth = 1000; TargetKb = 200 }
    'news-1.jpg'          = @{ Aspect = 3 / 2;  MaxWidth = 1000; TargetKb = 150 }
    'news-2.jpg'          = @{ Aspect = 3 / 2;  MaxWidth = 1000; TargetKb = 150 }
    'news-3.jpg'          = @{ Aspect = 3 / 2;  MaxWidth = 1000; TargetKb = 150 }
    'news-4.jpg'          = @{ Aspect = 3 / 2;  MaxWidth = 1000; TargetKb = 150 }
    'print-brochures.jpg' = @{ Aspect = 4 / 3;  MaxWidth = 900;  TargetKb = 140 }
    'print-canvas.jpg'    = @{ Aspect = 4 / 3;  MaxWidth = 900;  TargetKb = 140 }
    'print-cards.jpg'     = @{ Aspect = 4 / 3;  MaxWidth = 900;  TargetKb = 140 }
    'print-docs.jpg'      = @{ Aspect = 4 / 3;  MaxWidth = 900;  TargetKb = 140 }
    'print-large.jpg'     = @{ Aspect = 4 / 3;  MaxWidth = 900;  TargetKb = 140 }
    'print-mug.jpg'       = @{ Aspect = 4 / 3;  MaxWidth = 900;  TargetKb = 140 }
    'print-scanning.jpg'  = @{ Aspect = 4 / 3;  MaxWidth = 900;  TargetKb = 140 }
    'print-tshirts.jpg'   = @{ Aspect = 4 / 3;  MaxWidth = 900;  TargetKb = 140 }
}

$qualitySteps = @(82, 78, 74, 70, 66, 62)

$encoder = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() |
    Where-Object { $_.MimeType -eq 'image/jpeg' }

if (-not $encoder) {
    throw 'JPEG-энкодер System.Drawing не найден.'
}

function Save-Jpeg {
    param(
        [System.Drawing.Bitmap]$Bitmap,
        [string]$Path,
        [int]$Quality
    )

    $parameters = New-Object System.Drawing.Imaging.EncoderParameters(1)
    $parameters.Param[0] = New-Object System.Drawing.Imaging.EncoderParameter(
        [System.Drawing.Imaging.Encoder]::Quality,
        [int64]$Quality
    )
    $Bitmap.Save($Path, $encoder, $parameters)
    $parameters.Dispose()
}

$report = @()

foreach ($file in Get-ChildItem $dir -Filter *.jpg | Sort-Object Name) {
    $rule = $settings[$file.Name]
    if (-not $rule) { continue }

    $before = $file.Length
    $source = [System.Drawing.Image]::FromFile($file.FullName)
    $width = $source.Width
    $height = $source.Height

    # Кадрирование по центру под нужные пропорции: ровно то, что делал object-fit: cover.
    $aspect = $rule.Aspect
    if (($width / $height) -gt $aspect) {
        $cropHeight = $height
        $cropWidth = [int][Math]::Round($height * $aspect)
    } else {
        $cropWidth = $width
        $cropHeight = [int][Math]::Round($width / $aspect)
    }
    $cropX = [int][Math]::Floor(($width - $cropWidth) / 2)
    $cropY = [int][Math]::Floor(($height - $cropHeight) / 2)

    $scale = if ($cropWidth -gt $rule.MaxWidth) { $rule.MaxWidth / $cropWidth } else { 1 }
    $newWidth = [int][Math]::Round($cropWidth * $scale)
    $newHeight = [int][Math]::Round($cropHeight * $scale)

    $bitmap = New-Object System.Drawing.Bitmap($newWidth, $newHeight)
    $bitmap.SetResolution(72, 72)
    $g = [System.Drawing.Graphics]::FromImage($bitmap)
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $g.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
    $g.DrawImage(
        $source,
        (New-Object System.Drawing.Rectangle(0, 0, $newWidth, $newHeight)),
        $cropX, $cropY, $cropWidth, $cropHeight,
        [System.Drawing.GraphicsUnit]::Pixel
    )
    $g.Dispose()
    $source.Dispose()

    $temp = [System.IO.Path]::ChangeExtension([System.IO.Path]::GetTempFileName(), '.jpg')
    $chosen = $null

    foreach ($quality in $qualitySteps) {
        Save-Jpeg -Bitmap $bitmap -Path $temp -Quality $quality
        $size = (Get-Item $temp).Length
        if (-not $chosen -or $size -lt $chosen.Size) {
            $chosen = [pscustomobject]@{ Quality = $quality; Size = $size }
        }
        if ($size -le $rule.TargetKb * 1KB) { break }
    }

    $bitmap.Dispose()

    if ($chosen.Size -lt $before) {
        Move-Item -Force $temp $file.FullName
        $report += [pscustomobject]@{
            File    = $file.Name
            Size    = "$newWidth" + 'x' + "$newHeight"
            Quality = $chosen.Quality
            Before  = [math]::Round($before / 1KB)
            After   = [math]::Round($chosen.Size / 1KB)
            Saved   = "$([math]::Round((1 - $chosen.Size / $before) * 100))%"
        }
    } else {
        Remove-Item -Force $temp
        $report += [pscustomobject]@{
            File    = $file.Name
            Size    = "$newWidth" + 'x' + "$newHeight"
            Quality = '-'
            Before  = [math]::Round($before / 1KB)
            After   = [math]::Round($before / 1KB)
            Saved   = '0%'
        }
    }
}

$report | Format-Table -AutoSize

$totalBefore = ($report | Measure-Object -Property Before -Sum).Sum
$totalAfter = ($report | Measure-Object -Property After -Sum).Sum
"Итого: $totalBefore КБ -> $totalAfter КБ (экономия $([math]::Round((1 - $totalAfter / $totalBefore) * 100))%)"
