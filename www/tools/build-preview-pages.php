<?php
/**
 * Сборка статического демо-сайта из демо-данных.
 *
 * Зачем: в репозитории нет ядра Битрикса, поэтому /services/, /news/, /events/
 * локально не открываются (нужен bitrix/header.php). Скрипт собирает
 * самодостаточный статический сайт в каталоге preview/ с теми же адресами,
 * что и в Битриксе:
 *
 *   /                     → preview/index.html
 *   /services/            → preview/services/index.html
 *   /services/<код>/      → preview/services/<код>/index.html
 *   /news/, /events/, /printing/, /books/, /collections/ — так же
 *   /contacts/, /feedback/, /subscribe/, /auth/, /rules/, /privacy/,
 *   /terms/, /sitemap/ — служебные и текстовые страницы
 *
 * Запуск (из корня репозитория):
 *   php tools/build-preview-pages.php
 *
 * Просмотр:
 *   php -S 127.0.0.1:8791 -t preview
 *   → http://127.0.0.1:8791/services/
 *
 * Вёрстка и данные не дублируются вручную: страницы собираются из
 * db/config/data/*.php и db/config/iblocks.php, каркас (шапка, подвал,
 * модальные окна) берётся из preview/index.html. Это демо для просмотра:
 * рабочая версия — PHP-страницы в корне репозитория и шаблоны в local/.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$previewDir = $root . '/preview';
$tplDir = $root . '/local/templates/nlb_bootstrap';

// Сборщик использует тот же хелпер <picture>, что и шаблон Битрикса:
// файлы вариантов ищутся в preview/assets, адреса получают префикс глубины.
require_once $root . '/local/php_interface/include/nlb_media.php';
if (!defined('NLB_MEDIA_ROOT')) {
    define('NLB_MEDIA_ROOT', $previewDir);
}

/* ---------------------------------------------------------------------- */
/* Хелперы                                                                */
/* ---------------------------------------------------------------------- */

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function load_data(string $root, string $name): array
{
    $file = $root . '/db/config/data/' . $name . '.php';
    if (!is_file($file)) {
        fwrite(STDERR, "Нет файла данных: {$file}\n");
        exit(1);
    }
    $data = require $file;

    return is_array($data) ? $data : [];
}

function load_config(string $root): array
{
    $file = $root . '/db/config/iblocks.php';
    if (!is_file($file)) {
        fwrite(STDERR, "Нет файла конфигурации: {$file}\n");
        exit(1);
    }
    $config = require $file;

    return is_array($config) ? $config : [];
}

function base(int $depth): string
{
    return $depth > 0 ? str_repeat('../', $depth) : '';
}

/** Ссылка на раздел сайта с учётом глубины страницы. */
function link_to(int $depth, string $path): string
{
    return base($depth) . ltrim($path, '/');
}

/** Картинка из ассетов (в данных путь вида assets/images/hero-1.jpg). */
function image_url(int $depth, string $relative): string
{
    return base($depth) . ltrim($relative, '/');
}

/** Ширины вывода: карточки, печать, обложка статьи, hero. */
const PICTURE_SIZES_CARD = '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 33vw';
const PICTURE_SIZES_PRINT = '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw';
const PICTURE_SIZES_COVER = '(max-width: 991px) 100vw, 760px';
const PICTURE_SIZES_GALLERY = '(max-width: 991px) 50vw, 360px';

/**
 * <picture> с AVIF/WebP и JPG-фолбэком — как nlb_picture() в шаблоне.
 * $alt принимается в escaped-виде, как в остальном сборщике.
 */
function picture_html(
    int $depth,
    string $relative,
    string $alt,
    string $sizes,
    string $class = '',
    bool $priority = false
): string {
    return nlb_picture(
        ltrim($relative, '/'),
        html_entity_decode($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        [
            'class'    => $class,
            'sizes'    => $sizes,
            'prefix'   => base($depth),
            'priority' => $priority,
        ]
    );
}

function ru_month(int $month, bool $short = false): string
{
    $shortNames = [
        1 => 'янв', 2 => 'фев', 3 => 'мар', 4 => 'апр', 5 => 'мая', 6 => 'июн',
        7 => 'июл', 8 => 'авг', 9 => 'сен', 10 => 'окт', 11 => 'ноя', 12 => 'дек',
    ];
    $fullNames = [
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая',
        6 => 'июня', 7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября',
        11 => 'ноября', 12 => 'декабря',
    ];

    return ($short ? $shortNames : $fullNames)[$month] ?? '';
}

function ru_date(string $iso, bool $withTime = false): string
{
    $timestamp = $iso !== '' ? strtotime($iso) : false;
    if (!$timestamp) {
        return '';
    }
    $text = (int)date('j', $timestamp) . ' ' . ru_month((int)date('n', $timestamp))
        . ' ' . date('Y', $timestamp);
    if ($withTime) {
        $text .= ', ' . date('H:i', $timestamp);
    }

    return $text;
}

function dmy(string $iso): string
{
    $timestamp = $iso !== '' ? strtotime($iso) : false;

    return $timestamp ? date('d.m.Y', $timestamp) : '';
}

function price($value): string
{
    $number = (int)$value;

    return $number > 0 ? 'от ' . number_format($number, 0, '', ' ') . ' ₽' : '';
}

/* ---------------------------------------------------------------------- */
/* Каркас: шапка и подвал из preview/index.html                           */
/* ---------------------------------------------------------------------- */

function strip_fonts(string $html): string
{
    $html = (string)preg_replace('~\s*<link rel="preconnect" href="https://fonts[^>]*>~', '', $html);
    $html = (string)preg_replace('~\s*<link href="https://fonts\.googleapis\.com[^>]*>~', '', $html);

    return $html;
}

function nav_items(): array
{
    return [
        ['Главная', '', 'home', []],
        ['Новости', 'news/', 'news', []],
        ['События', 'events/', 'events', []],
        ['Услуги', 'services/', 'services', [
            ['Все услуги', 'services/'],
            ['Обзорная площадка', 'services/observation-deck/'],
            ['Сауна', 'services/sauna/'],
            ['Кафе', 'services/cafe/'],
            ['Лекторий', 'services/lecture-hall/'],
            ['Концертный зал', 'services/concert-hall/'],
            ['Детская комната', 'services/kids-zone/'],
            ['Спортзал', 'services/gym/'],
            ['Коворкинг', 'services/coworking/'],
        ]],
        ['Печать', 'printing/', 'printing', [
            ['Все услуги печати', 'printing/'],
            ['Печать и копирование', 'printing/pechat-dokumentov/'],
            ['Сканирование', 'printing/skanirovanie/'],
            ['Переплёт и ламинирование', 'printing/pereplyot-i-laminirovanie/'],
            ['Визитки', 'printing/vizitki/'],
            ['Брошюры и буклеты', 'printing/broshyury-i-buklety/'],
            ['Широкоформатная печать', 'printing/shirokiy-format/'],
            ['Печать на холсте', 'printing/pechat-na-holste/'],
            ['Печать на кружках', 'printing/kruzhki/'],
        ]],
        ['Каталог продукции', 'books/', 'books', []],
        ['Контакты', 'contacts/', 'contacts', []],
    ];
}

function nav_html(int $depth, string $active): string
{
    $prefix = base($depth);
    $html = '<ul class="navbar-nav mx-auto">' . "\n";
    foreach (nav_items() as [$label, $path, $key, $children]) {
        $href = $path === '' ? ($prefix === '' ? './' : $prefix) : $prefix . $path;
        $isActive = $key === $active;

        if (!$children) {
            $html .= '                    <li class="nav-item"><a class="nav-link'
                . ($isActive ? ' active' : '') . '" href="' . e($href) . '"'
                . ($isActive ? ' aria-current="page"' : '') . '>' . e($label) . '</a></li>' . "\n";
            continue;
        }

        $html .= '                    <li class="nav-item dropdown"><a class="nav-link dropdown-toggle'
            . ($isActive ? ' active' : '') . '" href="' . e($href) . '" role="button"'
            . ' data-bs-toggle="dropdown" aria-expanded="false"'
            . ($isActive ? ' aria-current="page"' : '') . '>' . e($label) . '</a>' . "\n"
            . '                        <ul class="dropdown-menu">' . "\n";
        foreach ($children as [$childLabel, $childPath]) {
            $html .= '                            <li><a class="dropdown-item" href="'
                . e($prefix . $childPath) . '">' . e($childLabel) . '</a></li>' . "\n";
        }
        $html .= '                        </ul>' . "\n"
            . '                    </li>' . "\n";
    }

    return $html . '                </ul>';
}

function replace_nav(string $html, int $depth, string $active): string
{
    $start = strpos($html, '<ul class="navbar-nav mx-auto">');
    if ($start === false) {
        return $html;
    }

    // Ищем парный </ul> с учётом вложенных выпадающих списков.
    $end = false;
    $offset = $start;
    $level = 0;
    while (preg_match('~</?ul\b~i', $html, $match, PREG_OFFSET_CAPTURE, $offset)) {
        $tag = $match[0][0];
        $offset = $match[0][1] + strlen($tag);
        if (str_contains($tag, '/')) {
            $level--;
            if ($level === 0) {
                $end = $match[0][1];
                break;
            }
        } else {
            $level++;
        }
    }
    if ($end === false) {
        return $html;
    }

    return substr($html, 0, $start) . nav_html($depth, $active) . substr($html, $end + 5);
}

/** Ссылки главной: якоря → реальные разделы. */
function rewrite_home_links(string $html): string
{
    $pairs = [
        'href="#contacts" aria-label="Уведомления"' => 'href="auth/" aria-label="Уведомления"',
        'href="#contacts" aria-label="Личный кабинет"' => 'href="auth/" aria-label="Личный кабинет"',
        'href="#contacts">Войти<' => 'href="auth/">Войти<',
        'href="#contacts">Правила<' => 'href="rules/">Правила<',
        'href="#contacts">Политика конфиденциальности<' => 'href="privacy/">Политика конфиденциальности<',
        'href="#contacts">Условия использования<' => 'href="terms/">Условия использования<',
        'href="#contacts">Карта сайта<' => 'href="sitemap/">Карта сайта<',
        'class="print-link" href="#contacts"' => 'class="print-link" href="printing/"',
        'href="#print">Все услуги<' => 'href="services/">Все услуги<',
        'href="printing/">Все услуги<' => 'href="services/">Все услуги<',
        'href="#contacts">Перейти в каталог<' => 'href="printing/">Перейти в каталог<',
        'class="event-btn" href="#contacts"' => 'class="event-btn" href="events/"',
        'href="#contacts">Подписаться на рассылку<' => 'href="subscribe/">Подписаться на рассылку<',
        'action="#contacts"' => 'action="feedback/"',
        'action="#contacts" method="get" role="search"' => 'action="sitemap/" method="get" role="search"',
        'href="#top"' => 'href="./"',
        'href="#news">Все события<' => 'href="events/">Все мероприятия<',
        'href="news/">Все события<' => 'href="events/">Все мероприятия<',
        'href="#news"' => 'href="news/"',
        'href="#events"' => 'href="events/"',
        'href="#services"' => 'href="services/"',
        'href="#print"' => 'href="printing/"',
        'href="#collections"' => 'href="collections/"',
        'href="#contacts"' => 'href="contacts/"',
    ];

    return strtr($html, $pairs);
}

function sync_sprite(string $html, string $tplDir): string
{
    $header = @file_get_contents($tplDir . '/header.php');
    if ($header === false) {
        return $html;
    }
    if (!preg_match(
        '~<svg xmlns="http://www\.w3\.org/2000/svg" width="0" height="0"[^>]*>(.*?)</svg>~s',
        $header,
        $source
    )) {
        return $html;
    }

    $replacement = '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" '
        . 'style="position:absolute;overflow:hidden" aria-hidden="true" focusable="false">'
        . $source[1] . '</svg>';

    $count = 0;
    $result = (string)preg_replace(
        '~<svg xmlns="http://www\.w3\.org/2000/svg" width="0" height="0"[^>]*>.*?</svg>~s',
        $replacement,
        $html,
        1,
        $count
    );

    return $count > 0 ? $result : $html;
}

/**
 * Готовит главную: убирает внешние шрифты, переводит ссылки на разделы
 * и пути к ассетам. Идемпотентно: повторный запуск ничего не ломает.
 */
function prepare_home(string $previewDir): void
{
    global $tplDir;
    $file = $previewDir . '/index.html';
    $html = (string)file_get_contents($file);
    $html = strip_fonts($html);
    $html = sync_sprite($html, $tplDir);
    $html = rewrite_home_links($html);
    $html = replace_nav($html, 0, 'home');
    $html = str_replace('../local/templates/nlb_bootstrap/', '', $html);
    $html = upgrade_home_pictures($html);
    $html = version_assets($html, $tplDir);
    file_put_contents($file, $html);
}

/**
 * Версия у styles.css и script.js: ?v=<8 символов sha256>.
 *
 * Без неё браузер держит старый файл после пересборки превью: сервер
 * (python -m http.server, php -S) не отдаёт Cache-Control, и правка
 * стилей выглядит как «сломанный» виджет. Хеш зависит от содержимого,
 * а не от времени, поэтому повторная сборка остаётся побайтово той же.
 */
function version_assets(string $html, string $tplDir): string
{
    foreach (['styles.css', 'script.js'] as $file) {
        $path = $tplDir . '/' . $file;
        if (!is_file($path)) {
            continue;
        }
        $version = substr((string)hash_file('sha256', $path), 0, 8);
        $html = (string)preg_replace(
            '~((?:href|src)="(?:\.\./)*)' . preg_quote($file, '~') . '(?:\?v=[0-9a-f]+)?"~',
            '$1' . $file . '?v=' . $version . '"',
            $html
        );
    }

    return $html;
}

/**
 * Главная в preview — статический слепок вёрстки. Заменяет <img> из
 * assets/images на <picture> с вариантами; уже собранные <picture>
 * сначала разворачивает, поэтому повторная сборка ничего не меняет.
 */
function upgrade_home_pictures(string $html): string
{
    $html = (string)preg_replace_callback(
        '~<picture class="nlb-picture">\s*(?:<source\b[^>]*>\s*)*<img\b[^>]*>\s*</picture>~',
        static function (array $m): string {
            preg_match('~<img\b[^>]*>~', $m[0], $img);

            return $img[0] ?? $m[0];
        },
        $html
    );

    return (string)preg_replace_callback(
        '~<img\b([^>]*?)\s*/?>~',
        static function (array $m): string {
            $attrs = $m[1];
            if (!preg_match('~\bsrc="(assets/images/[^"]+\.jpe?g)"~', $attrs, $srcMatch)) {
                return $m[0];
            }
            $relative = $srcMatch[1];
            if (!is_file(NLB_MEDIA_ROOT . '/' . $relative)) {
                return $m[0];
            }
            $class = preg_match('~\bclass="([^"]*)"~', $attrs, $classMatch) ? $classMatch[1] : '';
            $alt = preg_match('~\balt="([^"]*)"~', $attrs, $altMatch) ? $altMatch[1] : '';
            $file = basename($relative);
            $sizes = PICTURE_SIZES_CARD;
            if (str_starts_with($file, 'hero-')) {
                $sizes = '100vw';
            } elseif (str_starts_with($file, 'print-')) {
                $sizes = PICTURE_SIZES_PRINT;
            }

            return picture_html(0, $relative, $alt, $sizes, $class, str_contains($class, 'hero-img'));
        },
        $html
    );
}

function home_template(string $previewDir): string
{
    static $html = null;
    if ($html === null) {
        $html = strip_fonts((string)file_get_contents($previewDir . '/index.html'));
    }

    return $html;
}

/** Добавляет ../ к относительным ссылкам и путям ассетов. */
function rebase(string $html, int $depth): string
{
    if ($depth <= 0) {
        return $html;
    }
    $prefix = base($depth);

    return (string)preg_replace_callback(
        '~\b(href|src|action)="(?!https?:|mailto:|tel:|#|/|\.\./|data:)([^"]*)"~',
        static function (array $m) use ($prefix): string {
            return $m[1] . '="' . $prefix . $m[2] . '"';
        },
        $html
    );
}

function layout(string $previewDir, string $title, string $description, string $content, int $depth, string $active): string
{
    $home = home_template($previewDir);
    $mainPos = strpos($home, '<main class="site-main" id="main">');
    $footerPos = strpos($home, '<footer class="site-footer" id="contacts">');
    if ($mainPos === false || $footerPos === false) {
        fwrite(STDERR, "Не найдены границы main/footer в preview/index.html\n");
        exit(1);
    }

    $head = rebase(substr($home, 0, $mainPos), $depth);
    $tail = rebase(substr($home, $footerPos), $depth);

    $head = (string)preg_replace('~<title>.*?</title>~s', '<title>' . e($title) . '</title>', $head, 1);
    $head = (string)preg_replace(
        '~<meta name="description" content="[^"]*">~',
        '<meta name="description" content="' . e($description) . '">',
        $head,
        1
    );
    $head = replace_nav($head, $depth, $active);

    return $head . '<main class="site-main" id="main">' . $content . "</main>\n\n" . $tail;
}

/* ---------------------------------------------------------------------- */
/* Общие блоки                                                            */
/* ---------------------------------------------------------------------- */

function filter_script(): string
{
    return <<<'HTML'

<script>
(function () {
    document.querySelectorAll('[data-nlb-filter]').forEach(function (list) {
        list.addEventListener('click', function (event) {
            var button = event.target.closest('[data-filter]');
            if (!button) {
                return;
            }
            var value = button.getAttribute('data-filter');
            list.querySelectorAll('[data-filter]').forEach(function (one) {
                one.classList.toggle('is-active', one === button);
            });
            document.querySelectorAll('[data-nlb-item]').forEach(function (card) {
                card.hidden = value !== 'all' && card.getAttribute('data-nlb-item') !== value;
            });
        });
    });
})();
</script>
HTML;
}

function chips(array $items, string $allLabel): string
{
    $html = '<li><button class="section-chip is-active" type="button" data-filter="all">'
        . e($allLabel) . '</button></li>';
    foreach ($items as $value => $label) {
        $html .= '<li><button class="section-chip" type="button" data-filter="' . e($value) . '">'
            . e($label) . '</button></li>';
    }

    return $html;
}

/* ---------------------------------------------------------------------- */
/* Карточки                                                               */
/* ---------------------------------------------------------------------- */

function news_cards(array $items, int $depth): string
{
    $cards = '';
    foreach ($items as $item) {
        $href = link_to($depth, 'news/' . $item['code'] . '/');
        $cards .= '<div class="col-sm-6 col-lg-4" data-nlb-item="' . e($item['section'] ?? '') . '">'
            . '<article class="nlb-card h-100"><a class="d-block h-100" href="' . e($href) . '">'
            . '<div class="nlb-card__media">'
            . picture_html($depth, (string)($item['image'] ?? 'assets/images/news-1.jpg'), (string)$item['name'], PICTURE_SIZES_CARD)
            . '</div>'
            . '<div class="nlb-card__body">'
            . '<p class="news-date">' . e(dmy((string)($item['active_from'] ?? ''))) . '</p>'
            . '<h3 class="news-title">' . e($item['name']) . '</h3>'
            . '<p class="news-text clamp-2">' . e($item['preview_text'] ?? '') . '</p>'
            . '</div></a></article></div>';
    }

    return $cards;
}

function event_cards(array $items, int $depth, array $config): string
{
    $values = $config['iblocks']['events']['properties']['CATEGORY']['values'] ?? [];
    $names = [];
    foreach ($values as $value) {
        $names[$value['xml_id']] = $value['value'];
    }
    $classes = [
        'literature' => 'bg-grad-sky', 'children' => 'bg-grad-pink',
        'tech' => 'bg-grad-green', 'lecture' => 'bg-grad-orange',
    ];

    $cards = '';
    foreach ($items as $item) {
        $props = $item['properties'] ?? [];
        $timestamp = !empty($props['DATE']) ? strtotime((string)$props['DATE']) : false;
        $category = (string)($props['CATEGORY'] ?? '');
        $href = link_to($depth, 'events/' . $item['code'] . '/');
        $seats = (int)($props['SEATS'] ?? 0);

        $cards .= '<div class="col-sm-6 col-lg-4" data-nlb-item="' . e($category) . '">'
            . '<article class="nlb-card h-100"><div class="nlb-card__media">'
            . picture_html($depth, (string)($item['image'] ?? 'assets/images/event.jpg'), (string)$item['name'], PICTURE_SIZES_CARD)
            . '<span class="event-cat ' . e($classes[$category] ?? 'bg-grad-sky') . '">'
            . e($names[$category] ?? 'Событие') . '</span>';
        if ($timestamp) {
            $cards .= '<span class="event-date"><b>' . date('d', $timestamp) . '</b>'
                . '<span>' . e(ru_month((int)date('n', $timestamp), true)) . '</span></span>';
        }
        $cards .= '</div><div class="nlb-card__body">'
            . '<h3 class="event-title"><a class="text-reset" href="' . e($href) . '">'
            . e($item['name']) . '</a></h3>'
            . '<p class="event-text clamp-2">' . e($item['preview_text'] ?? '') . '</p>'
            . '<div class="event-meta">';
        if ($timestamp) {
            $cards .= '<span><svg class="i i-20" aria-hidden="true"><use href="#i-clock"/></svg>'
                . date('H:i', $timestamp) . '</span>';
        }
        $cards .= '<span><svg class="i i-20" aria-hidden="true"><use href="#i-pin"/></svg>'
            . e($props['PLACE'] ?? 'Библиотека') . '</span>';
        if ($seats > 0) {
            $cards .= '<span><svg class="i i-20" aria-hidden="true"><use href="#i-users"/></svg>'
                . $seats . ' мест</span>';
        }
        $cards .= '</div><a class="event-btn" href="'
            . e(link_to($depth, 'feedback/')) . '">Зарегистрироваться</a>'
            . '</div></article></div>';
    }

    return $cards;
}

function service_cards(array $items, int $depth): string
{
    $cards = '';
    foreach ($items as $item) {
        $props = $item['properties'] ?? [];
        $color = (string)($props['COLOR'] ?? 'primary');
        $icon = (string)($props['ICON'] ?? 'sparkles');
        $name = e($item['name']);
        $href = link_to($depth, 'services/' . $item['code'] . '/');
        $subtitle = trim((string)($props['SUBTITLE'] ?? ''));
        $preview = (string)($item['preview_text'] ?? '');

        $priceLabel = trim((string)($props['PRICE_LABEL'] ?? ''));
        if ($priceLabel === '') {
            $priceLabel = price($props['PRICE_FROM'] ?? 0);
        }
        if ($priceLabel === '') {
            $priceLabel = 'Бесплатно для читателей';
        }

        $features = '';
        foreach ((array)($props['FEATURES'] ?? []) as $feature) {
            $parts = explode('|', (string)$feature, 2);
            $title = trim($parts[0]);
            if ($title === '') {
                continue;
            }
            $features .= '<li><span class="service-tile-check bg-grad-' . e($color) . '">'
                . '<svg class="i" aria-hidden="true"><use href="#i-check"/></svg></span>'
                . e($title) . '</li>';
        }

        $cards .= '<div class="col-md-6 col-xl-4"><article class="service-tile h-100">'
            . '<span class="service-tile-icon bg-grad-' . e($color) . '">'
            . '<svg class="i" aria-hidden="true"><use href="#i-' . e($icon) . '"/></svg></span>'
            . ($subtitle !== '' ? '<p class="service-tile-eyebrow">' . e($subtitle) . '</p>' : '')
            . '<h3 class="service-tile-title"><a class="text-reset" href="' . e($href) . '">'
            . $name . '</a></h3>'
            . ($preview !== '' ? '<p class="service-tile-text">' . e($preview) . '</p>' : '')
            . '<span class="service-tile-price">' . e($priceLabel) . '</span>'
            . ($features !== '' ? '<ul class="service-tile-points">' . $features . '</ul>' : '')
            . '<div class="service-tile-actions">'
            . '<a class="btn btn-nlb btn-icon" href="' . e($href) . '#booking">Забронировать'
            . '<svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg></a>'
            . '<a class="btn btn-nlb-outline" href="' . e($href) . '">Узнать подробнее</a>'
            . '</div></article></div>';
    }

    return $cards;
}

function printing_cards(array $items, int $depth, int $limit = 0): string
{
    if ($limit > 0) {
        $items = array_slice($items, 0, $limit);
    }
    $cards = '';
    foreach ($items as $item) {
        $props = $item['properties'] ?? [];
        $color = (string)($props['COLOR'] ?? 'primary');
        $name = e($item['name']);
        $href = link_to($depth, 'printing/' . $item['code'] . '/');
        $priceText = price($props['PRICE_FROM'] ?? 0);
        $unit = (string)($props['UNIT'] ?? '');

        $cards .= '<div class="col-sm-6 col-xl-3"><article class="print-card h-100">'
            . '<div class="print-media">'
            . picture_html($depth, (string)($item['image'] ?? 'assets/images/print-docs.jpg'), (string)$item['name'], PICTURE_SIZES_PRINT)
            . '<span class="print-badge bg-grad-' . e($color) . '">'
            . '<svg class="i" aria-hidden="true"><use href="#i-' . e($props['ICON'] ?? 'printer') . '"/></svg>'
            . '</span></div><div class="print-body">'
            . '<h3 class="print-title">' . $name . '</h3>'
            . '<p class="print-text">' . e($item['preview_text'] ?? '') . '</p>';
        if ($priceText !== '') {
            $cards .= '<div class="fw-bold text-dark mb-2">' . e($priceText)
                . ($unit !== '' ? ' <span class="small fw-normal text-muted">· ' . e($unit) . '</span>' : '')
                . '</div>';
        }
        $cards .= '<a class="print-link" href="' . e($href) . '">Подробнее'
            . '<svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg></a>'
            . '</div></article></div>';
    }

    return $cards;
}

function book_cards(array $items, int $depth): string
{
    $palette = ['primary', 'green', 'sky', 'pink', 'orange'];
    $statuses = [
        'available' => ['В наличии', 'is-available'],
        'reserved' => ['Под заказ', 'is-reserved'],
        'unavailable' => ['Нет в наличии', 'is-unavailable'],
    ];

    $cards = '';
    foreach ($items as $item) {
        $props = $item['properties'] ?? [];
        $name = (string)$item['name'];
        $cover = $palette[abs(crc32($name)) % count($palette)];
        $available = (string)($props['AVAILABLE'] ?? 'available');
        [$statusText, $statusClass] = $statuses[$available] ?? ['Нет в наличии', 'is-unavailable'];
        $material = (string)($props['AUTHOR'] ?? '');
        $size = (string)($props['YEAR'] ?? '');
        $price = (int)($props['PRICE'] ?? 0);
        $href = link_to($depth, 'books/' . $item['code'] . '/');

        $cards .= '<div class="col-sm-6 col-lg-4 col-xl-3" data-nlb-item="' . e($item['section'] ?? '') . '">'
            . '<article class="book-card h-100">'
            . '<a class="book-cover book-cover--' . $cover . '" href="' . e($href)
            . '" aria-hidden="true" tabindex="-1">'
            . '<svg class="i" aria-hidden="true"><use href="#i-package"/></svg>'
            . '<span>' . e(mb_substr($name, 0, 28)) . '</span></a>'
            . '<div class="book-body"><span class="book-status ' . $statusClass . '">' . $statusText . '</span>'
            . '<h3 class="book-title"><a class="text-reset" href="' . e($href) . '">' . e($name) . '</a></h3>'
            . '<p class="book-author">' . e($material . ($size !== '' ? ', ' . $size : '')) . '</p>'
            . ($price > 0 ? '<p class="book-price">' . number_format($price, 0, '', ' ') . ' ₽</p>' : '')
            . '<p class="book-text clamp-2">' . e($item['preview_text'] ?? '') . '</p>'
            . '</div></article></div>';
    }

    return $cards;
}

function collection_cards(array $items, int $depth): string
{
    $colors = ['primary' => 'blue', 'green' => 'green', 'sky' => 'sky', 'pink' => 'violet', 'orange' => 'orange'];
    $cards = '';
    foreach ($items as $item) {
        $props = $item['properties'] ?? [];
        $color = (string)($props['COLOR'] ?? 'primary');
        $class = $colors[$color] ?? 'blue';
        $count = (int)($props['COUNT'] ?? 0);
        $href = link_to($depth, 'collections/' . $item['code'] . '/');

        $cards .= '<div class="col-md-6 col-lg-4"><div class="collection-card collection-card--' . $class . ' h-100">'
            . '<span class="icon-tile icon-tile--' . $class . '">'
            . '<svg class="i" aria-hidden="true"><use href="#i-' . e($props['ICON'] ?? 'book') . '"/></svg></span>'
            . '<h3>' . e($item['name']) . '</h3>'
            . ($count > 0 ? '<div class="collection-count">Предметов: ' . $count . '</div>' : '')
            . '<p class="collection-text">' . e($item['preview_text'] ?? '') . '</p>'
            . '<a class="collection-more" href="' . e($href) . '">Узнать больше'
            . '<svg class="i i-20" aria-hidden="true"><use href="#i-arrow-right"/></svg></a>'
            . '</div></div>';
    }

    return $cards;
}

/* ---------------------------------------------------------------------- */
/* Детальная страница                                                     */
/* ---------------------------------------------------------------------- */

function detail_page(string $previewDir, array $o): string
{
    $depth = 2;
    $name = e($o['name']);
    $lead = e($o['preview'] ?? '');
    $html = (string)($o['html'] ?? '');
    $rows = (string)($o['rows'] ?? '');
    $back = link_to($depth, $o['back'] . '/');

    $cover = '';
    if (!empty($o['image'])) {
        $cover = '<figure class="article-cover">'
            . picture_html($depth, (string)$o['image'], (string)$o['name'], PICTURE_SIZES_COVER)
            . '</figure>';
    }
    $cta = '';
    if (!empty($o['cta'])) {
        $cta = '<a class="btn ' . e($o['cta']['class'] ?? 'btn-nlb') . ' w-100 mt-3" href="'
            . e(link_to($depth, (string)$o['cta']['href'])) . '">' . e($o['cta']['label']) . '</a>';
    }

    $content = '<section class="section"><div class="container"><article class="article">'
        . '<header class="article-head">'
        . (!empty($o['date']) ? '<p class="article-meta">'
            . '<svg class="i i-20" aria-hidden="true"><use href="#i-calendar"/></svg>'
            . e($o['date']) . '</p>' : '')
        . '<h1 class="page-title">' . $name . '</h1>'
        . '<p class="section-lead">' . $lead . '</p>'
        . (!empty($o['tags']) ? '<ul class="tag-list" aria-label="Теги">' . $o['tags'] . '</ul>' : '')
        . '</header>'
        . $cover
        . '<div class="article-body"><div class="bitrix-content">' . $html . '</div>'
        . '<aside class="article-aside"><h2 class="h5 mb-3">Коротко</h2>'
        . '<dl class="article-props">' . $rows . '</dl>' . $cta . '</aside></div>'
        . '<footer class="article-footer"><a class="btn btn-nlb-outline btn-icon" href="' . e($back) . '">'
        . '<svg class="i" aria-hidden="true"><use href="#i-chevron-left"/></svg>'
        . e($o['backText']) . '</a></footer>'
        . '</article></div></section>'
        . (string)($o['after'] ?? '');

    return layout($previewDir, $o['name'] . ' — Библиотека 2026', (string)($o['preview'] ?? ''), $content, $depth, $o['active']);
}

/* ---------------------------------------------------------------------- */
/* Страницы разделов                                                      */
/* ---------------------------------------------------------------------- */

function service_page(string $previewDir, array $item, int $depth): string
{
    $props = $item['properties'] ?? [];
    $name = e($item['name']);
    $code = e((string)($item['code'] ?? ''));
    $color = (string)($props['COLOR'] ?? 'primary');
    $icon = (string)($props['ICON'] ?? 'sparkles');
    $subtitle = trim((string)($props['SUBTITLE'] ?? ''));
    $detail = (string)($item['detail_text'] ?? '');
    $preview = (string)($item['preview_text'] ?? '');
    $schedule = (array)($props['SCHEDULE'] ?? []);
    $phone = trim((string)($props['PHONE'] ?? ''));
    $email = trim((string)($props['EMAIL'] ?? ''));
    $picture = !empty($item['image']) ? (string)$item['image'] : '';

    $body = $detail !== '' ? $detail : '<p>' . e($preview) . '</p>';

    $meta = '';
    if ($schedule || $phone !== '' || $email !== '') {
        $meta = '<div class="service-meta">';
        if ($schedule) {
            $lines = '';
            foreach ($schedule as $line) {
                $parts = explode(':', (string)$line, 2);
                $day = trim($parts[0]);
                $time = trim($parts[1] ?? '');
                $lines .= '<div>' . ($time !== ''
                    ? '<span class="service-meta-strong">' . e($day) . ':</span> ' . e($time)
                    : e((string)$line)) . '</div>';
            }
            $meta .= '<div class="service-meta-card service-meta-card--' . e($color) . '">'
                . '<div class="service-meta-head"><svg class="i" aria-hidden="true"><use href="#i-clock"/></svg>'
                . '<h2>Режим работы</h2></div>'
                . '<div class="service-meta-lines">' . $lines . '</div></div>';
        }
        if ($phone !== '' || $email !== '') {
            $links = '';
            if ($phone !== '') {
                $links .= '<div><a href="tel:' . e((string)preg_replace('/[^+\d]/', '', $phone)) . '">'
                    . e($phone) . '</a></div>';
            }
            if ($email !== '') {
                $links .= '<div><a href="mailto:' . e($email) . '">' . e($email) . '</a></div>';
            }
            $meta .= '<div class="service-meta-card service-meta-card--contacts">'
                . '<div class="service-meta-head"><svg class="i" aria-hidden="true"><use href="#i-phone"/></svg>'
                . '<h2>Контакты</h2></div>'
                . '<div class="service-meta-lines">' . $links . '</div></div>';
        }
        $meta .= '</div>';
    }

    $gallery = '<div class="service-gallery">';
    $emptyCount = 3;
    if ($picture !== '') {
        $gallery .= '<figure class="service-gallery-item">'
            . picture_html($depth, $picture, (string)$item['name'], PICTURE_SIZES_GALLERY)
            . '</figure>';
        $emptyCount = 2;
    }
    for ($i = 0; $i < $emptyCount; $i++) {
        $gallery .= '<div class="service-gallery-item service-gallery-empty" aria-hidden="true">'
            . '<svg class="i" aria-hidden="true"><use href="#i-' . e($icon) . '"/></svg>'
            . '<span>Фото скоро появится</span></div>';
    }
    $gallery .= '</div>';

    $featuresHtml = '';
    $featureCards = '';
    foreach ((array)($props['FEATURES'] ?? []) as $feature) {
        $parts = explode('|', (string)$feature, 2);
        $title = trim($parts[0]);
        if ($title === '') {
            continue;
        }
        $text = trim($parts[1] ?? '');
        $featureCards .= '<div class="col-sm-6 col-lg-4"><article class="service-feature-item h-100">'
            . '<span class="service-feature-item-icon bg-grad-' . e($color) . '">'
            . '<svg class="i" aria-hidden="true"><use href="#i-' . e($icon) . '"/></svg></span>'
            . '<h3>' . e($title) . '</h3>'
            . ($text !== '' ? '<p>' . e($text) . '</p>' : '')
            . '</article></div>';
    }
    if ($featureCards !== '') {
        $featuresHtml = '<section class="service-section"><div class="section-head">'
            . '<h2 class="section-title">Что входит в услугу</h2></div>'
            . '<div class="row g-4">' . $featureCards . '</div></section>';
    }

    $tariffsHtml = '';
    $tariffCards = '';
    foreach ((array)($props['TARIFFS'] ?? []) as $tariff) {
        $parts = array_map('trim', explode('|', (string)$tariff));
        $title = (string)($parts[0] ?? '');
        if ($title === '') {
            continue;
        }
        $tariffPrice = (string)($parts[1] ?? '');
        $includes = [];
        if (!empty($parts[3])) {
            foreach (explode(';', $parts[3]) as $one) {
                $one = trim($one);
                if ($one !== '') {
                    $includes[] = $one;
                }
            }
        }
        if (!empty($parts[2])) {
            array_unshift($includes, (string)$parts[2]);
        }
        $includes = array_values(array_unique($includes));
        $popular = trim((string)($parts[4] ?? '')) !== '';

        $list = '';
        foreach ($includes as $include) {
            $list .= '<li><svg class="i" aria-hidden="true"><use href="#i-check"/></svg>'
                . e($include) . '</li>';
        }

        $tariffCards .= '<div class="col-md-6 col-lg-4 d-flex">'
            . '<article class="tariff-card h-100' . ($popular ? ' tariff-card--popular' : '') . '">'
            . ($popular ? '<span class="tariff-badge">Популярный</span>' : '')
            . '<h3>' . e($title) . '</h3>'
            . '<p class="tariff-price">' . e($tariffPrice) . '</p>'
            . ($list !== '' ? '<ul class="tariff-list">' . $list . '</ul>' : '')
            . '<a class="btn btn-nlb w-100 mt-auto js-choose-tariff" href="#booking" data-tariff="'
            . e($title) . '" data-price="' . e($tariffPrice) . '">Выбрать тариф</a>'
            . '</article></div>';
    }
    if ($tariffCards !== '') {
        $tariffsHtml = '<section class="service-section"><div class="section-head">'
            . '<h2 class="section-title">Цены и тарифы</h2></div>'
            . '<div class="row g-4 align-items-stretch">' . $tariffCards . '</div></section>';
    }

    $feedbackHref = link_to($depth, 'feedback/');
    $privacyHref = link_to($depth, 'privacy/');
    $timeOptions = '<option value="">Выберите время</option>';
    for ($hour = 10; $hour <= 21; $hour++) {
        $time = sprintf('%02d:00', $hour);
        $timeOptions .= '<option value="' . e($time) . '">' . e($time) . '</option>';
    }
    $booking = '<section class="section section--booking" id="booking"><div class="container">'
        . '<div class="booking-card"><div class="booking-head">'
        . '<span class="booking-icon"><svg class="i" aria-hidden="true"><use href="#i-calendar"/></svg></span>'
        . '<h2 class="section-title">Забронировать</h2>'
        . '<p class="section-lead mb-0">Заполните форму, и мы свяжемся с вами для подтверждения брони.</p>'
        . '</div>'
        . '<form class="feedback-form needs-validation booking-form" action="' . e($feedbackHref)
        . '" method="post" novalidate data-nlb-form="booking" data-nlb-slots="{}">'
        . '<input type="hidden" name="NAME" data-nlb-field="NAME" value="Бронь: ' . $name . '">'
        . '<input type="hidden" name="PROPERTY[SERVICE][0]" data-nlb-field="SERVICE" value="' . $name . '">'
        . '<input type="hidden" name="PROPERTY[SERVICE_CODE][0]" data-nlb-field="SERVICE_CODE" value="' . $code . '">'
        . '<input type="hidden" name="PROPERTY[TARIFF][0]" data-nlb-field="TARIFF" value="">'
        . '<input type="hidden" name="PROPERTY[PRICE][0]" data-nlb-field="PRICE" value="">'
        . '<input type="hidden" name="PROPERTY[STATUS][0]" data-nlb-field="STATUS" value="new">'
        . '<div class="row g-3">'
        . '<div class="col-sm-6"><label class="form-label" for="nlbBookingDate">Дата *</label>'
        . '<input class="form-control" id="nlbBookingDate" type="date" data-nlb-field="DATE" name="PROPERTY[DATE][0]" required>'
        . '<div class="invalid-feedback">Выберите дату.</div></div>'
        . '<div class="col-sm-6"><label class="form-label" for="nlbBookingTime">Время *</label>'
        . '<select class="form-select" id="nlbBookingTime" data-nlb-field="TIME" name="PROPERTY[TIME][0]" required>'
        . $timeOptions . '</select>'
        . '<div class="form-text" data-nlb-slots-hint></div>'
        . '<div class="form-text">Демо: часть слотов занята — они отмечены и недоступны.</div>'
        . '<div class="invalid-feedback">Выберите время.</div></div>'
        . '<div class="col-md-6"><label class="form-label" for="nlbBookingName">Имя *</label>'
        . '<input class="form-control" id="nlbBookingName" type="text" data-nlb-field="PERSON" name="PROPERTY[PERSON][0]" autocomplete="name" placeholder="Ваше имя" required>'
        . '<div class="invalid-feedback">Укажите имя.</div></div>'
        . '<div class="col-md-6"><label class="form-label" for="nlbBookingPhone">Телефон *</label>'
        . '<input class="form-control" id="nlbBookingPhone" type="tel" data-nlb-field="PHONE" name="PROPERTY[PHONE][0]" autocomplete="tel" placeholder="+375 (__) ___-__-__" required>'
        . '<div class="invalid-feedback">Укажите телефон.</div></div>'
        . '<div class="col-12"><label class="form-label" for="nlbBookingEmail">E-mail</label>'
        . '<input class="form-control" id="nlbBookingEmail" type="email" data-nlb-field="EMAIL" name="PROPERTY[EMAIL][0]" autocomplete="email" placeholder="you@example.by">'
        . '<div class="invalid-feedback">Укажите корректный e-mail.</div></div>'
        . '<div class="col-12"><label class="form-label" for="nlbBookingComment">Комментарий</label>'
        . '<textarea class="form-control" id="nlbBookingComment" data-nlb-field="COMMENT" name="PROPERTY[COMMENT][0]" rows="4" placeholder="Дополнительная информация или пожелания…"></textarea></div>'
        . '<div class="col-12"><div class="form-check">'
        . '<input class="form-check-input" id="nlbBookingConsent" type="checkbox" name="CONSENT" value="Y" required>'
        . '<label class="form-check-label small" for="nlbBookingConsent">Согласен на обработку персональных данных</label>'
        . '<div class="invalid-feedback">Нужно согласие.</div></div></div>'
        . '</div>'
        . '<button class="btn btn-nlb w-100 mt-3 btn-icon" type="submit" name="iblock_submit" value="Y">Отправить заявку'
        . '<svg class="i" aria-hidden="true"><use href="#i-send"/></svg></button>'
        . '</form>'
        . '<p class="form-hint mt-3 mb-0 text-center">* Обязательные поля. Мы свяжемся с вами в течение часа '
        . 'для подтверждения брони. Отправляя форму, вы соглашаетесь с '
        . '<a href="' . e($privacyHref) . '">политикой конфиденциальности</a>.</p>'
        . '</div></div></section>';

    $demoBooking = <<<'HTML'
    <script>
    (function () {
        var form = document.querySelector('form[data-nlb-form="booking"]');
        if (!form) return;
        var pad = function (n) { return (n < 10 ? '0' : '') + n; };
        var iso = function (days) {
            var d = new Date();
            d.setDate(d.getDate() + days);
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        };
        var all = [];
        for (var h = 10; h <= 21; h++) {
            all.push(pad(h) + ':00');
        }
        var slots = {};
        slots[iso(1)] = ['10:00', '11:00', '14:00', '18:00', '19:00'];
        slots[iso(2)] = all;
        slots[iso(4)] = ['12:00', '13:00', '20:00', '21:00'];
        slots[iso(7)] = ['10:00', '16:00', '17:00'];
        form.setAttribute('data-nlb-slots', JSON.stringify(slots));
        var date = form.querySelector('[data-nlb-field="DATE"]');
        if (date) {
            date.min = iso(0);
        }
    })();
    </script>
    HTML;
    $booking .= $demoBooking;

    $content = '<section class="section service-detail-section"><div class="container">'
        . '<article class="service-detail">'
        . '<a class="service-back" href="' . e(link_to($depth, 'services/')) . '">'
        . '<svg class="i" aria-hidden="true"><use href="#i-chevron-left"/></svg>Назад к услугам</a>'
        . '<header class="service-detail-head">'
        . '<span class="service-detail-icon bg-grad-' . e($color) . '">'
        . '<svg class="i" aria-hidden="true"><use href="#i-' . e($icon) . '"/></svg></span>'
        . '<div><h1 class="page-title mb-1">' . $name . '</h1>'
        . ($subtitle !== '' ? '<p class="service-detail-subtitle">' . e($subtitle) . '</p>' : '')
        . '</div></header>'
        . '<div class="service-detail-body bitrix-content">' . $body . '</div>'
        . $meta
        . '</article>'
        . '<section class="service-section"><div class="section-head">'
        . '<h2 class="section-title">Галерея</h2>'
        . '<p class="section-lead">Фотографии пространства. Присылайте свои снимки — добавим в галерею.</p>'
        . '</div>' . $gallery
        . '<div class="service-gallery-actions">'
        . '<a class="btn btn-nlb-outline btn-icon" href="' . e($feedbackHref) . '">'
        . '<svg class="i" aria-hidden="true"><use href="#i-plus"/></svg>Предложить фото</a>'
        . '</div></section>'
        . $featuresHtml
        . $tariffsHtml
        . '</div></section>'
        . $booking;

    return layout(
        $previewDir,
        $item['name'] . ' — Библиотека 2026',
        (string)($item['preview_text'] ?? ''),
        $content,
        $depth,
        'services'
    );
}

function build_news(string $root, string $previewDir, array $config): string
{
    $data = load_data($root, 'news');
    $sections = [];
    foreach (($config['iblocks']['news']['sections_data'] ?? []) as $section) {
        $sections[$section['code']] = $section['name'];
    }
    $chips = chips($sections, 'Все новости');

    $content = <<<HTML

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--green">Блог</span>
        <h1 class="page-title">Новости</h1>
        <p class="page-lead">
            Что происходит в библиотеке: новые поступления, цифровые проекты,
            партнёрства и анонсы мероприятий.
        </p>

        <ul class="section-chips" aria-label="Рубрики новостей" data-nlb-filter>
            {$chips}
        </ul>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-4">

HTML
        . news_cards($data['items'] ?? [], 1)
        . <<<'HTML'

        </div>
    </div>
</section>
HTML
        . filter_script();

    return layout(
        $previewDir,
        'Новости — Библиотека 2026',
        'Новости библиотеки: новые поступления, проекты, партнёрства и анонсы мероприятий.',
        $content,
        1,
        'news'
    );
}

function build_events(string $root, string $previewDir, array $config): string
{
    $data = load_data($root, 'events');
    $categories = [];
    foreach (($config['iblocks']['events']['properties']['CATEGORY']['values'] ?? []) as $value) {
        $categories[$value['xml_id']] = $value['value'];
    }
    $chips = chips($categories, 'Все рубрики');

    $content = <<<HTML

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--red">Афиша</span>
        <h1 class="page-title">События</h1>
        <p class="page-lead">
            Лекции, мастер-классы и встречи с авторами. Вход свободный или по записи —
            смотрите условия в карточке события.
        </p>

        <ul class="section-chips" aria-label="Рубрики событий" data-nlb-filter>
            {$chips}
        </ul>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-4">

HTML
        . event_cards($data['items'] ?? [], 1, $config)
        . <<<'HTML'

        </div>
    </div>
</section>

<section class="section section--soft">
    <div class="container">
        <div class="cta-banner" data-reveal>
            <h2>Хотите провести своё событие?</h2>
            <p class="text-white-75 mb-4">
                Расскажите о формате и дате — подберём зал и поможем с организацией.
            </p>
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a class="btn btn-nlb-white btn-lg-nlb" href="../feedback/">Написать нам</a>
                <a class="btn btn-nlb-outline btn-lg-nlb" href="../services/">Услуги и залы</a>
            </div>
        </div>
    </div>
</section>
HTML
        . filter_script();

    return layout(
        $previewDir,
        'События — Библиотека 2026',
        'Афиша библиотеки: лекции, мастер-классы, встречи с авторами и детские занятия.',
        $content,
        1,
        'events'
    );
}

function build_services(string $root, string $previewDir): string
{
    $services = load_data($root, 'services');
    $printing = load_data($root, 'printing');

    $content = <<<HTML

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--violet">
            <svg class="i" aria-hidden="true"><use href="#i-sparkles"/></svg>
            Премиальные услуги
        </span>
        <h1 class="page-title">Наши услуги</h1>
        <p class="page-lead">
            Библиотека 2026 — это больше, чем просто книги. Мы создали уникальное
            пространство для вашего комфорта и развития: от обзорной площадки
            и сауны до лектория и коворкинга.
        </p>
    </div>
</section>

<section class="section pt-0" id="services-list">
    <div class="container">
        <div class="row g-4 service-grid">

HTML
        . service_cards($services['items'] ?? [], 1)
        . <<<'HTML'

        </div>
    </div>
</section>

<section class="section section--soft">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow eyebrow--green">Полиграфия</span>
            <h2 class="section-title">Каталог печатных услуг</h2>
            <p class="section-lead">
                Печать документов, сканирование, переплёт, визитки, сувениры
                и фотопечать — рядом с читальными залами.
            </p>
        </div>

        <div class="row g-4">

HTML
        . printing_cards($printing['items'] ?? [], 1, 8)
        . <<<'HTML'

        </div>

        <div class="text-center mt-5" data-reveal>
            <a class="btn btn-nlb-green btn-lg-nlb btn-icon" href="../printing/">
                Перейти в каталог
                <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
            </a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4" data-reveal>
                <div class="info-card">
                    <span class="icon-tile icon-tile--blue mb-3">
                        <svg class="i" aria-hidden="true"><use href="#i-clock"/></svg>
                    </span>
                    <h3>График работы</h3>
                    <p class="text-muted mb-0">
                        Ежедневно с 8:00 до 22:00.<br>Без выходных.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-reveal>
                <div class="info-card">
                    <span class="icon-tile icon-tile--violet mb-3">
                        <svg class="i" aria-hidden="true"><use href="#i-users"/></svg>
                    </span>
                    <h3>Для читателей</h3>
                    <p class="text-muted mb-0">
                        Специальные цены для держателей читательского билета.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-reveal>
                <div class="info-card">
                    <span class="icon-tile icon-tile--green mb-3">
                        <svg class="i" aria-hidden="true"><use href="#i-check"/></svg>
                    </span>
                    <h3>Бронирование</h3>
                    <p class="text-muted mb-0">
                        Онлайн-бронирование доступно 24/7 через сайт или приложение.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--soft">
    <div class="container">
        <div class="cta-banner" data-reveal>
            <h2>Готовы начать?</h2>
            <p class="text-white-75 mb-4">
                Забронируйте любую услугу онлайн или свяжитесь с нами
                для получения дополнительной информации.
            </p>
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a class="btn btn-nlb-white btn-lg-nlb" href="#services-list">Забронировать сейчас</a>
                <a class="btn btn-nlb-outline btn-lg-nlb" href="../feedback/">Связаться с нами</a>
            </div>
        </div>
    </div>
</section>
HTML;

    return layout(
        $previewDir,
        'Услуги — Библиотека 2026',
        'Премиальные услуги библиотеки: обзорная площадка, сауна, кафе, лекторий, концертный зал, детская комната, спортзал и коворкинг.',
        $content,
        1,
        'services'
    );
}

function build_printing(string $root, string $previewDir): string
{
    $data = load_data($root, 'printing');

    $content = <<<HTML

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--violet">Полиграфия</span>
        <h1 class="page-title">Каталог печатных услуг</h1>
        <p class="page-lead">
            От визиток до широкоформатных баннеров — всё, что нужно для бизнеса
            и творчества. Печать документов, сувениры, фото и фирменный стиль.
        </p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-4">

HTML
        . printing_cards($data['items'] ?? [], 1)
        . <<<'HTML'

        </div>

        <p class="form-hint mt-4 mb-0">
            Цены указаны «от» и зависят от объёма, бумаги и срочности.
            Точную стоимость подтвердит сотрудник после расчёта заказа.
        </p>
    </div>
</section>

<section class="section section--soft">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow eyebrow--red">Как заказать</span>
            <h2 class="section-title">От файла до готового тиража</h2>
        </div>

        <div class="row g-4">
            <div class="col-md-4" data-reveal>
                <div class="info-card">
                    <span class="icon-tile icon-tile--blue mb-3">
                        <svg class="i" aria-hidden="true"><use href="#i-file"/></svg>
                    </span>
                    <h3>1. Пришлите файл</h3>
                    <p class="text-muted mb-0">
                        PDF, DOCX или изображение. Проверим макет и подскажем,
                        если нужно поправить поля или шрифты.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-reveal>
                <div class="info-card">
                    <span class="icon-tile icon-tile--green mb-3">
                        <svg class="i" aria-hidden="true"><use href="#i-package"/></svg>
                    </span>
                    <h3>2. Выберите параметры</h3>
                    <p class="text-muted mb-0">
                        Бумага, цветность, двусторонняя печать, переплёт.
                        Сотрудник посчитает итоговую стоимость.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-reveal>
                <div class="info-card">
                    <span class="icon-tile icon-tile--orange mb-3">
                        <svg class="i" aria-hidden="true"><use href="#i-printer"/></svg>
                    </span>
                    <h3>3. Заберите заказ</h3>
                    <p class="text-muted mb-0">
                        Небольшие заказы печатаем в день обращения. О готовности
                        сообщим по телефону или e-mail.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="cta-banner" data-reveal>
            <h2>Нужен расчёт тиража?</h2>
            <p class="text-white-75 mb-4">
                Опишите задачу: количество страниц, экземпляров и сроки —
                ответим с ценой и вариантами материалов.
            </p>
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a class="btn btn-nlb-white btn-lg-nlb" href="../feedback/">Запросить расчёт</a>
                <a class="btn btn-nlb-outline btn-lg-nlb" href="../services/">Другие услуги</a>
            </div>
        </div>
    </div>
</section>
HTML;

    return layout(
        $previewDir,
        'Печатные услуги — Библиотека 2026',
        'Печать документов, сканирование, переплёт, визитки и сувениры в библиотеке.',
        $content,
        1,
        'printing'
    );
}

function build_books(string $root, string $previewDir, array $config): string
{
    $data = load_data($root, 'books');
    $sections = [];
    foreach (($config['iblocks']['books']['sections_data'] ?? []) as $section) {
        $sections[$section['code']] = $section['name'];
    }
    $chips = chips($sections, 'Все сувениры');

    $content = <<<HTML

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--violet">Сувениры</span>
        <h1 class="page-title">Сувениры и подарки</h1>
        <p class="page-lead">
            Брелоки, фарфор, кружки, одежда, канцелярия, магниты и аксессуары
            для дома и автомобиля. Наличие и цену уточняйте на карточке товара.
        </p>

        <ul class="section-chips" aria-label="Категории" data-nlb-filter>
            {$chips}
        </ul>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-4" aria-label="Обозначения наличия">
            <span class="book-status is-available">В наличии</span>
            <span class="book-status is-reserved">Под заказ</span>
            <span class="book-status is-unavailable">Нет в наличии</span>
            <span class="small text-muted">Статус указан на карточке каждого товара.</span>
        </div>

        <div class="row g-4">

HTML
        . book_cards($data['items'] ?? [], 1)
        . <<<'HTML'

        </div>
    </div>
</section>

<section class="section section--soft">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-7" data-reveal>
                <span class="eyebrow eyebrow--green">Подборки</span>
                <h2 class="section-title">Не знаете, что выбрать?</h2>
                <p class="section-lead mb-0">
                    Собрали готовые наборы: для коллег, детей, в дорогу и на память
                    о Минске.
                </p>
            </div>
            <div class="col-lg-5 text-lg-end" data-reveal>
                <a class="btn btn-nlb-green btn-lg-nlb btn-icon" href="../collections/">
                    Смотреть подборки
                    <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>
HTML
        . filter_script();

    return layout(
        $previewDir,
        'Сувениры и подарки — Библиотека 2026',
        'Брелоки, фарфор, кружки, одежда, канцелярия, магниты и аксессуары для дома и автомобиля.',
        $content,
        1,
        'books'
    );
}

function build_collections(string $root, string $previewDir): string
{
    $data = load_data($root, 'collections');

    $content = <<<HTML

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--violet">Наборы</span>
        <h1 class="page-title">Подарочные наборы</h1>
        <p class="page-lead">
            Собрали сувениры в готовые комплекты: для коллег, дома, детей,
            путешествий и на память о Минске. Внутри — состав и упаковка.
        </p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-4">

HTML
        . collection_cards($data['items'] ?? [], 1)
        . <<<'HTML'

        </div>
    </div>
</section>

<section class="section section--soft">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-7" data-reveal>
                <span class="eyebrow eyebrow--green">Каталог</span>
                <h2 class="section-title">Хотите собрать свой набор?</h2>
                <p class="section-lead mb-0">
                    В каталоге — материал, размер, артикул, цена и наличие.
                    Любые предметы можно сложить в один подарок.
                </p>
            </div>
            <div class="col-lg-5 text-lg-end" data-reveal>
                <a class="btn btn-nlb btn-lg-nlb" href="../books/">Перейти в каталог</a>
            </div>
        </div>
    </div>
</section>
HTML;

    return layout(
        $previewDir,
        'Подарочные наборы — Библиотека 2026',
        'Готовые подарочные наборы сувениров библиотеки: для коллег, дома, детей, путешествий и на память о Минске.',
        $content,
        1,
        'collections'
    );
}

/* ---------------------------------------------------------------------- */
/* Текстовые страницы из PHP-исходников                                   */
/* ---------------------------------------------------------------------- */

function static_content(string $root, string $relative, int $depth): string
{
    $source = (string)file_get_contents($root . '/' . $relative);
    $source = (string)preg_replace('~^<\?php.*?\?>\s*~s', '', $source, 1);
    $source = (string)preg_replace('~<\?php\s*require\s+\$_SERVER\[.DOCUMENT_ROOT.\].*?\?>\s*$~s', '', $source);
    $source = str_replace('<?= SITE_DIR ?>', base($depth), $source);
    $source = (string)preg_replace('~<\?(?:php|=).*?\?>~s', '', $source);

    return trim($source);
}

function build_contacts(string $root, string $previewDir): string
{
    return layout(
        $previewDir,
        'Контакты — Библиотека 2026',
        'Контакты библиотеки: адрес, часы работы, телефон и обратная связь.',
        static_content($root, 'contacts/index.php', 1),
        1,
        'contacts'
    );
}

function build_feedback(string $previewDir): string
{
    $content = <<<'HTML'

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--green">Написать</span>
        <h1 class="page-title">Обратная связь</h1>
        <p class="page-lead">
            Задайте вопрос, предложите сувенир или расскажите о проблеме.
            Отвечаем в течение рабочего дня.
        </p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7" data-reveal>
                <div class="info-card">
                    <h2 class="h4 mb-3">Форма сообщения</h2>
                    <form class="needs-validation" action="./" method="post" novalidate data-nlb-form="request">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="nlbFbName">Имя *</label>
                                <input class="form-control" id="nlbFbName" type="text" name="NAME" autocomplete="name" required>
                                <div class="invalid-feedback">Укажите имя.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="nlbFbEmail">E-mail *</label>
                                <input class="form-control" id="nlbFbEmail" type="email" name="PROPERTY[EMAIL][0]" autocomplete="email" required>
                                <div class="invalid-feedback">Укажите корректный e-mail.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="nlbFbPhone">Телефон</label>
                                <input class="form-control" id="nlbFbPhone" type="tel" name="PROPERTY[PHONE][0]" autocomplete="tel" placeholder="+375 (__) ___-__-__">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="nlbFbSubject">Тема</label>
                                <input class="form-control" id="nlbFbSubject" type="text" name="PROPERTY[SUBJECT][0]" placeholder="Вопрос, предложение, отзыв">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="nlbFbMessage">Сообщение *</label>
                                <textarea class="form-control" id="nlbFbMessage" name="PROPERTY[MESSAGE][0]" rows="5" required></textarea>
                                <div class="invalid-feedback">Напишите сообщение.</div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" id="nlbFbConsent" type="checkbox" name="CONSENT" value="Y" required>
                                    <label class="form-check-label" for="nlbFbConsent">Согласен с
                                        <a href="../privacy/">политикой конфиденциальности</a></label>
                                    <div class="invalid-feedback">Нужно согласие.</div>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-nlb mt-3" type="submit" name="iblock_submit" value="Y">Отправить</button>
                        <p class="form-hint mt-3 mb-0">
                            Обращение сохраняется в инфоблоке <code>requests</code> (компонент
                            <code>bitrix:iblock.element.add.form</code>) и дублируется письмом администратору.
                        </p>
                    </form>
                </div>
            </div>
            <div class="col-lg-5" data-reveal>
                <div class="info-card mb-4">
                    <h2 class="h5">Быстрее по телефону</h2>
                    <div class="contact-line">
                        <svg class="i" aria-hidden="true"><use href="#i-phone"/></svg>
                        <span><a href="tel:+375173000000">+375 (17) 300-00-00</a></span>
                    </div>
                    <div class="contact-line">
                        <svg class="i" aria-hidden="true"><use href="#i-mail"/></svg>
                        <span><a href="mailto:info@example.by">info@example.by</a></span>
                    </div>
                    <div class="contact-line">
                        <svg class="i" aria-hidden="true"><use href="#i-clock"/></svg>
                        <span>Ежедневно 10:00–22:00</span>
                    </div>
                </div>

                <div class="info-card">
                    <h2 class="h5">Частые вопросы</h2>
                    <ul class="mb-0">
                        <li>Как записаться в библиотеку — <a href="../services/">услуги</a>.</li>
                        <li>Где посмотреть сувениры — <a href="../books/">каталог продукции</a>.</li>
                        <li>Как заказать печать — <a href="../printing/">каталог печати</a>.</li>
                        <li>Куда прийти — <a href="../contacts/">контакты</a>.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
HTML;

    return layout(
        $previewDir,
        'Обратная связь — Библиотека 2026',
        'Напишите в библиотеку: вопрос о фондах, услугах, событиях или сотрудничестве.',
        $content,
        1,
        ''
    );
}

function build_subscribe(string $previewDir): string
{
    $content = <<<'HTML'

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--violet">Рассылка</span>
        <h1 class="page-title">Подписка на рассылку</h1>
        <p class="page-lead">
            Анонсы событий, новые поступления и важные объявления — одно письмо
            в неделю. Без спама, отписка в один клик.
        </p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7" data-reveal>
                <div class="info-card">
                    <h2 class="h4 mb-3">Оформить подписку</h2>
                    <form class="needs-validation" action="./" method="post" novalidate>
                        <div class="mb-3">
                            <label class="form-label" for="nlbSubEmail">E-mail</label>
                            <input class="form-control" id="nlbSubEmail" type="email" name="EMAIL"
                                   placeholder="you@example.com" autocomplete="email" required>
                            <div class="invalid-feedback">Укажите корректный e-mail.</div>
                        </div>
                        <button class="btn btn-nlb" type="submit">Подписаться</button>
                    </form>
                    <p class="form-hint mt-3 mb-0">
                        Демо-форма: в Битриксе подключается модуль «Рассылки»
                        (<code>bitrix:subscribe.form</code>) с двойным подтверждением.
                    </p>
                </div>
            </div>

            <div class="col-lg-5" data-reveal>
                <div class="info-card mb-4">
                    <h2 class="h5">Что в рассылке</h2>
                    <ul class="mb-0 ps-3">
                        <li>афиша событий на две недели;</li>
                        <li>новые сувениры и подарочные наборы;</li>
                        <li>изменения в работе залов и услуг;</li>
                        <li>сезонные проекты и конкурсы.</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h2 class="h5">Изменить или отменить</h2>
                    <p class="text-muted mb-0">
                        Управление подпиской — по e-mail, на который оформлена рассылка.
                        В Битриксе для этого служит компонент <code>bitrix:subscribe.edit</code>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
HTML;

    return layout(
        $previewDir,
        'Подписка на рассылку — Библиотека 2026',
        'Подпишитесь на анонсы событий и новости библиотеки. Отписка — в один клик.',
        $content,
        1,
        ''
    );
}

function build_auth(string $previewDir): string
{
    $content = <<<'HTML'

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--red">Личный кабинет</span>
        <h1 class="page-title">Вход в личный кабинет</h1>
        <p class="page-lead">
            В кабинете можно изменить данные профиля, управлять подпиской
            и посмотреть историю обращений.
        </p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7" data-reveal>
                <div class="info-card">
                    <h2 class="h4 mb-3">Вход</h2>
                    <form class="needs-validation" action="./" method="post" novalidate>
                        <div class="mb-3">
                            <label class="form-label" for="nlbAuthLogin">E-mail или логин</label>
                            <input class="form-control" id="nlbAuthLogin" type="text" name="USER_LOGIN"
                                   autocomplete="username" required>
                            <div class="invalid-feedback">Укажите логин.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="nlbAuthPass">Пароль</label>
                            <input class="form-control" id="nlbAuthPass" type="password" name="USER_PASSWORD"
                                   autocomplete="current-password" required>
                            <div class="invalid-feedback">Укажите пароль.</div>
                        </div>
                        <button class="btn btn-nlb" type="submit">Войти</button>
                        <a class="btn btn-nlb-outline ms-2" href="../feedback/">Забыли пароль?</a>
                    </form>
                    <p class="form-hint mt-3 mb-0">
                        Демо-форма: в Битриксе подключается <code>bitrix:system.auth.authorize</code>.
                    </p>
                </div>
            </div>

            <div class="col-lg-5" data-reveal>
                <div class="info-card">
                    <h2 class="h5">Ещё нет билета?</h2>
                    <p class="text-muted">
                        Читательский билет оформляется бесплатно при первом посещении:
                        нужен документ с фотографией.
                    </p>
                    <a class="btn btn-nlb-green" href="../services/">Как записаться</a>
                </div>
            </div>
        </div>
    </div>
</section>
HTML;

    return layout(
        $previewDir,
        'Вход — Библиотека 2026',
        'Вход в личный кабинет читателя: продление книг, подписка и история обращений.',
        $content,
        1,
        ''
    );
}

function build_sitemap(string $previewDir): string
{
    $sections = [
        ['/', 'Главная', 'hero-карусель, услуги, события, новости, коллекции'],
        ['services/', 'Услуги', '8 премиальных услуг и превью каталога печати'],
        ['news/', 'Новости', '10 новостей с рубриками'],
        ['events/', 'События', '6 событий с фильтром по рубрикам'],
        ['printing/', 'Печатные услуги', '37 позиций с ценами'],
        ['books/', 'Сувениры и подарки', '24 сувенира с ценами'],
        ['collections/', 'Подарочные наборы', '6 готовых комплектов сувениров'],
        ['contacts/', 'Контакты', 'адрес, часы, телефон'],
        ['feedback/', 'Обратная связь', 'форма сообщения'],
        ['subscribe/', 'Подписка', 'форма рассылки'],
        ['auth/', 'Вход', 'личный кабинет'],
        ['rules/', 'Правила пользования', 'как записаться и брать книги'],
        ['privacy/', 'Политика конфиденциальности', 'документ'],
        ['terms/', 'Условия использования', 'документ'],
        ['sitemap/', 'Карта сайта', 'эта страница'],
    ];

    $items = '';
    foreach ($sections as [$path, $name, $note]) {
        $href = $path === '' ? './' : '../' . $path;
        $items .= '<li class="mb-2"><a href="' . e($href) . '">' . e($name) . '</a>'
            . ' <span class="text-muted small">— ' . e($note) . '</span></li>';
    }

    $content = <<<HTML

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--green">Навигация</span>
        <h1 class="page-title">Карта сайта</h1>
        <p class="page-lead">Все разделы демо-сайта на одной странице.</p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="info-card">
                    <ul class="mb-0 ps-3">
                        {$items}
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
HTML;

    return layout(
        $previewDir,
        'Карта сайта — Библиотека 2026',
        'Карта демо-сайта библиотеки: услуги, новости, события, каталог и документы.',
        $content,
        1,
        ''
    );
}

/* ---------------------------------------------------------------------- */
/* Запись и ассеты                                                        */
/* ---------------------------------------------------------------------- */

function remove_tree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . '/' . $entry;
        if (is_dir($path)) {
            remove_tree($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

/** Удаляет подкаталоги, которых больше нет в данных (например, старые услуги). */
function prune_subdirs(string $dir, array $keep): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . '/' . $entry;
        if (!is_dir($path) || in_array($entry, $keep, true)) {
            continue;
        }
        remove_tree($path);
        echo '  - ' . str_replace('\\', '/', substr($path, strlen(dirname(__DIR__)) + 1))
            . " (устаревшая страница)\n";
    }
}

function copy_tree(string $from, string $to): int
{
    if (!is_dir($from)) {
        return 0;
    }
    if (!is_dir($to) && !mkdir($to, 0775, true) && !is_dir($to)) {
        fwrite(STDERR, "Не удалось создать каталог: {$to}\n");
        exit(1);
    }
    $count = 0;
    foreach (scandir($from) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $source = $from . '/' . $entry;
        $target = $to . '/' . $entry;
        if (is_dir($source)) {
            $count += copy_tree($source, $target);
        } else {
            copy($source, $target);
            $count++;
        }
    }

    return $count;
}

function write_page(string $file, string $html): void
{
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        fwrite(STDERR, "Не удалось создать каталог: {$dir}\n");
        exit(1);
    }
    if (file_put_contents($file, $html) === false) {
        fwrite(STDERR, "Не удалось записать: {$file}\n");
        exit(1);
    }
    echo '  + ' . str_replace('\\', '/', substr($file, strlen(dirname(__DIR__)) + 1))
        . ' (' . round(strlen($html) / 1024) . " КБ)\n";
}

/* ---------------------------------------------------------------------- */
/* Сборка                                                                 */
/* ---------------------------------------------------------------------- */

$config = load_config($root);

echo "Ассеты:\n";
$copied = copy_tree($tplDir . '/assets', $previewDir . '/assets');
copy($tplDir . '/styles.css', $previewDir . '/styles.css');
copy($tplDir . '/script.js', $previewDir . '/script.js');
echo "  + preview/assets/ ({$copied} файлов), styles.css, script.js\n";

echo "Главная:\n";
prepare_home($previewDir);
echo "  + preview/index.html\n";

// Старые плоские страницы (services.html и т. п.) заменены каталогами
// services/, news/, events/ — иначе в демо остаются ссылки на 404.
foreach (['services', 'news', 'events'] as $legacy) {
    $legacyFile = $previewDir . '/' . $legacy . '.html';
    if (is_file($legacyFile)) {
        unlink($legacyFile);
        echo "  - preview/{$legacy}.html (устаревшая плоская страница)\n";
    }
}

$pages = [
    'services/index.html' => build_services($root, $previewDir),
    'news/index.html' => build_news($root, $previewDir, $config),
    'events/index.html' => build_events($root, $previewDir, $config),
    'printing/index.html' => build_printing($root, $previewDir),
    'books/index.html' => build_books($root, $previewDir, $config),
    'collections/index.html' => build_collections($root, $previewDir),
    'contacts/index.html' => build_contacts($root, $previewDir),
    'feedback/index.html' => build_feedback($previewDir),
    'subscribe/index.html' => build_subscribe($previewDir),
    'auth/index.html' => build_auth($previewDir),
    'privacy/index.html' => layout(
        $previewDir,
        'Политика конфиденциальности — Библиотека 2026',
        'Как библиотека обрабатывает персональные данные посетителей сайта.',
        static_content($root, 'privacy/index.php', 1),
        1,
        ''
    ),
    'terms/index.html' => layout(
        $previewDir,
        'Условия использования — Библиотека 2026',
        'Правила работы с сайтом и материалами библиотеки.',
        static_content($root, 'terms/index.php', 1),
        1,
        ''
    ),
    'rules/index.html' => layout(
        $previewDir,
        'Правила пользования — Библиотека 2026',
        'Как записаться в библиотеку, работать в залах и брать книги домой.',
        static_content($root, 'rules/index.php', 1),
        1,
        ''
    ),
    'sitemap/index.html' => build_sitemap($previewDir),
];

echo "Разделы:\n";
foreach ($pages as $relative => $html) {
    write_page($previewDir . '/' . $relative, $html);
}

echo "Детальные страницы:\n";

$news = load_data($root, 'news');
$tagLabels = [];
foreach (($config['iblocks']['news']['properties']['TAGS']['values'] ?? []) as $value) {
    $tagLabels[$value['xml_id']] = $value['value'];
}
foreach (($news['items'] ?? []) as $item) {
    $props = $item['properties'] ?? [];
    $tags = '';
    foreach ((array)($props['TAGS'] ?? []) as $tag) {
        $tags .= '<li>' . e($tagLabels[$tag] ?? $tag) . '</li>';
    }
    $rows = '';
    if (!empty($props['AUTHOR'])) {
        $rows .= '<div><dt>Автор</dt><dd>' . e($props['AUTHOR']) . '</dd></div>';
    }
    if (!empty($props['READ_TIME'])) {
        $rows .= '<div><dt>Время чтения</dt><dd>' . e($props['READ_TIME']) . '</dd></div>';
    }
    $html = detail_page($previewDir, [
        'name' => $item['name'],
        'preview' => $item['preview_text'] ?? '',
        'html' => $item['detail_text'] ?? '',
        'image' => $item['image'] ?? '',
        'date' => ru_date((string)($item['active_from'] ?? '')),
        'tags' => $tags,
        'rows' => $rows,
        'back' => 'news',
        'backText' => 'Все новости',
        'active' => 'news',
    ]);
    write_page($previewDir . '/news/' . $item['code'] . '/index.html', $html);
}

$events = load_data($root, 'events');
$formatLabels = [
    'offline' => 'Офлайн', 'online' => 'Онлайн', 'hybrid' => 'Гибрид',
];
foreach (($events['items'] ?? []) as $item) {
    $props = $item['properties'] ?? [];
    $rows = '<div><dt>Когда</dt><dd>' . e(ru_date((string)($props['DATE'] ?? ''), true)) . '</dd></div>';
    if (!empty($props['PLACE'])) {
        $rows .= '<div><dt>Место</dt><dd>' . e($props['PLACE']) . '</dd></div>';
    }
    if (!empty($props['FORMAT'])) {
        $rows .= '<div><dt>Формат</dt><dd>' . e($formatLabels[$props['FORMAT']] ?? $props['FORMAT']) . '</dd></div>';
    }
    if (!empty($props['SEATS'])) {
        $rows .= '<div><dt>Мест</dt><dd>' . (int)$props['SEATS'] . '</dd></div>';
    }
    if (!empty($props['PRICE'])) {
        $rows .= '<div><dt>Стоимость</dt><dd>' . e($props['PRICE']) . '</dd></div>';
    }
    if (!empty($props['DURATION'])) {
        $rows .= '<div><dt>Длительность</dt><dd>' . e($props['DURATION']) . '</dd></div>';
    }
    $html = detail_page($previewDir, [
        'name' => $item['name'],
        'preview' => $item['preview_text'] ?? '',
        'html' => $item['detail_text'] ?? '',
        'image' => $item['image'] ?? '',
        'date' => ru_date((string)($props['DATE'] ?? ''), true),
        'rows' => $rows,
        'back' => 'events',
        'backText' => 'Все события',
        'active' => 'events',
        'cta' => ['label' => 'Зарегистрироваться', 'href' => 'feedback/', 'class' => 'btn-nlb-green'],
    ]);
    write_page($previewDir . '/events/' . $item['code'] . '/index.html', $html);
}

$services = load_data($root, 'services');
$serviceCodes = [];
foreach (($services['items'] ?? []) as $item) {
    $serviceCodes[] = (string)$item['code'];
    $html = service_page($previewDir, $item, 2);
    write_page($previewDir . '/services/' . $item['code'] . '/index.html', $html);
}
prune_subdirs($previewDir . '/services', $serviceCodes);

$printing = load_data($root, 'printing');
foreach (($printing['items'] ?? []) as $item) {
    $props = $item['properties'] ?? [];
    $priceText = price($props['PRICE_FROM'] ?? 0);
    $rows = '';
    if ($priceText !== '') {
        $rows .= '<div><dt>Цена</dt><dd>' . e($priceText) . '</dd></div>';
    }
    if (!empty($props['UNIT'])) {
        $rows .= '<div><dt>Единица</dt><dd>' . e($props['UNIT']) . '</dd></div>';
    }
    $html = detail_page($previewDir, [
        'name' => $item['name'],
        'preview' => $item['preview_text'] ?? '',
        'html' => $item['detail_text'] ?? '',
        'image' => $item['image'] ?? '',
        'rows' => $rows,
        'back' => 'printing',
        'backText' => 'Каталог печати',
        'active' => 'printing',
        'cta' => ['label' => 'Заказать', 'href' => 'feedback/', 'class' => 'btn-nlb-green'],
    ]);
    write_page($previewDir . '/printing/' . $item['code'] . '/index.html', $html);
}

$books = load_data($root, 'books');
$ageLabels = [];
foreach (($config['iblocks']['books']['properties']['AGE_LIMIT']['values'] ?? []) as $value) {
    $ageLabels[$value['xml_id']] = $value['value'];
}
$availableLabels = [
    'available' => 'В наличии', 'reserved' => 'Под заказ', 'unavailable' => 'Нет в наличии',
];
$bookLabel = [
    'AUTHOR' => 'Материал', 'YEAR' => 'Размер', 'PUBLISHER' => 'Упаковка',
    'ISBN' => 'Артикул', 'PAGES' => 'Вес, г', 'SHELF' => 'Состав',
];
foreach (($books['items'] ?? []) as $item) {
    $props = $item['properties'] ?? [];
    $rows = '';
    foreach ($bookLabel as $code => $label) {
        if (!empty($props[$code])) {
            $rows .= '<div><dt>' . e($label) . '</dt><dd>' . e($props[$code]) . '</dd></div>';
        }
    }
    if (!empty($props['PRICE'])) {
        $rows .= '<div><dt>Цена</dt><dd>'
            . number_format((int)$props['PRICE'], 0, '', ' ') . ' ₽</dd></div>';
    }
    if (!empty($props['AGE_LIMIT'])) {
        $rows .= '<div><dt>Кому подойдёт</dt><dd>'
            . e($ageLabels[$props['AGE_LIMIT']] ?? $props['AGE_LIMIT']) . '</dd></div>';
    }
    if (!empty($props['AVAILABLE'])) {
        $rows .= '<div><dt>Наличие</dt><dd>'
            . e($availableLabels[$props['AVAILABLE']] ?? $props['AVAILABLE']) . '</dd></div>';
    }
    $article = (string)($props['ISBN'] ?? '');
    $priceValue = (int)($props['PRICE'] ?? 0);
    $order = '<section class="section section--booking" id="order"><div class="container">'
        . '<div class="booking-card"><div class="booking-head">'
        . '<span class="booking-icon"><svg class="i" aria-hidden="true"><use href="#i-package"/></svg></span>'
        . '<h2 class="section-title">Заказать «' . e($item['name']) . '»</h2>'
        . '<p class="section-lead mb-0">Оставьте контакты — подтвердим наличие, способ оплаты и получения.</p>'
        . '</div>'
        . '<form class="feedback-form needs-validation" action="' . e(link_to(2, 'books/')) . '" method="post" novalidate data-nlb-form="order">'
        . '<input type="hidden" name="NAME" value="Заказ: ' . e($item['name']) . '">'
        . '<input type="hidden" name="PROPERTY[ITEM][0]" value="' . e($item['name']) . '">'
        . '<input type="hidden" name="PROPERTY[ARTICLE][0]" value="' . e($article) . '">'
        . '<input type="hidden" name="PROPERTY[PRICE][0]" value="' . ($priceValue > 0 ? $priceValue : '') . '">'
        . '<input type="hidden" name="PROPERTY[STATUS][0]" value="new">'
        . '<div class="row g-3">'
        . '<div class="col-sm-6"><label class="form-label" for="nlbOrderQty">Количество *</label>'
        . '<input class="form-control" id="nlbOrderQty" type="number" name="PROPERTY[QTY][0]" min="1" step="1" value="1" required>'
        . '<div class="invalid-feedback">Укажите количество.</div></div>'
        . '<div class="col-sm-6"><label class="form-label" for="nlbOrderName">Имя *</label>'
        . '<input class="form-control" id="nlbOrderName" type="text" name="PROPERTY[PERSON][0]" autocomplete="name" placeholder="Ваше имя" required>'
        . '<div class="invalid-feedback">Укажите имя.</div></div>'
        . '<div class="col-md-6"><label class="form-label" for="nlbOrderPhone">Телефон *</label>'
        . '<input class="form-control" id="nlbOrderPhone" type="tel" name="PROPERTY[PHONE][0]" autocomplete="tel" placeholder="+375 (__) ___-__-__" required>'
        . '<div class="invalid-feedback">Укажите телефон.</div></div>'
        . '<div class="col-md-6"><label class="form-label" for="nlbOrderEmail">E-mail</label>'
        . '<input class="form-control" id="nlbOrderEmail" type="email" name="PROPERTY[EMAIL][0]" autocomplete="email" placeholder="you@example.by"></div>'
        . '<div class="col-12"><label class="form-label" for="nlbOrderComment">Комментарий</label>'
        . '<textarea class="form-control" id="nlbOrderComment" name="PROPERTY[COMMENT][0]" rows="3" placeholder="Размер, цвет, сроки…"></textarea></div>'
        . '<div class="col-12"><div class="form-check">'
        . '<input class="form-check-input" id="nlbOrderConsent" type="checkbox" name="CONSENT" value="Y" required>'
        . '<label class="form-check-label small" for="nlbOrderConsent">Согласен на обработку персональных данных</label>'
        . '<div class="invalid-feedback">Нужно согласие.</div></div></div>'
        . '</div>'
        . '<button class="btn btn-nlb w-100 mt-3 btn-icon" type="submit" name="iblock_submit" value="Y">Оформить заказ'
        . '<svg class="i" aria-hidden="true"><use href="#i-send"/></svg></button>'
        . '</form>'
        . '<p class="form-hint mt-3 mb-0 text-center">Заказ сохраняется в инфоблоке <code>orders</code>. '
        . 'Отправляя форму, вы соглашаетесь с <a href="' . e(link_to(2, 'privacy/')) . '">политикой конфиденциальности</a>.</p>'
        . '</div></div></section>';
    $html = detail_page($previewDir, [
        'name' => $item['name'],
        'preview' => $item['preview_text'] ?? '',
        'html' => $item['detail_text'] ?? '',
        'rows' => $rows,
        'back' => 'books',
        'backText' => 'Сувениры и подарки',
        'active' => 'books',
        'cta' => ['label' => 'Заказать', 'href' => '#order', 'class' => 'btn-nlb-green'],
        'after' => $order,
    ]);
    write_page($previewDir . '/books/' . $item['code'] . '/index.html', $html);
}

$collections = load_data($root, 'collections');
foreach (($collections['items'] ?? []) as $item) {
    $props = $item['properties'] ?? [];
    $count = (int)($props['COUNT'] ?? 0);
    $rows = $count > 0 ? '<div><dt>Предметов в наборе</dt><dd>' . $count . '</dd></div>' : '';
    $html = detail_page($previewDir, [
        'name' => $item['name'],
        'preview' => $item['preview_text'] ?? '',
        'html' => $item['detail_text'] ?? '',
        'rows' => $rows,
        'back' => 'collections',
        'backText' => 'Все коллекции',
        'active' => 'collections',
        'cta' => ['label' => 'Смотреть каталог', 'href' => 'books/'],
    ]);
    write_page($previewDir . '/collections/' . $item['code'] . '/index.html', $html);
}

echo "Готово. Просмотр: php -S 127.0.0.1:8791 -t preview → /services/, /news/, /events/\n";
