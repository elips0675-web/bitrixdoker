<?php
/**
 * NLB Bootstrap — SEO-хелперы шаблона.
 *
 * Подключается из local/php_interface/init.php и вызывается из header.php.
 * Дополняет то, что уже отдаёт ShowHead() (title, description, keywords):
 * canonical, Open Graph, Twitter Card, robots и разметку schema.org.
 *
 * Значения берутся из самого шаблона: SetTitle() и SetPageProperty().
 * Дополнительных настроек не требуется, в БД ничего не пишется.
 *
 * Внимание: разметка проверена только синтаксически (php -l). Валидаторы
 * Google и Яндекс.Вебмастера требуют живой домен — это «не проверено».
 */

if (!function_exists('nlb_seo_escape')) {
    function nlb_seo_escape(string $value): string
    {
        return function_exists('htmlspecialcharsbx')
            ? htmlspecialcharsbx($value)
            : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('nlb_seo_scheme')) {
    function nlb_seo_scheme(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $forwarded = strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']);
            if ($forwarded === 'https' || $forwarded === 'http') {
                return $forwarded;
            }
        }

        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    }
}

if (!function_exists('nlb_seo_host')) {
    function nlb_seo_host(): string
    {
        if (defined('SITE_SERVER_NAME') && SITE_SERVER_NAME !== '') {
            return (string)SITE_SERVER_NAME;
        }

        return isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
    }
}

if (!function_exists('nlb_seo_base_url')) {
    function nlb_seo_base_url(): string
    {
        $host = nlb_seo_host();

        return $host === '' ? '' : nlb_seo_scheme() . '://' . $host;
    }
}

if (!function_exists('nlb_seo_page_path')) {
    /**
     * Путь без параметров запроса: фильтры (?section=, ?past=1, PAGEN_1)
     * канонизируются на базовую страницу — так в индексе не появляются дубли.
     */
    function nlb_seo_page_path(): string
    {
        global $APPLICATION;

        $path = '';

        if (is_object($APPLICATION) && method_exists($APPLICATION, 'GetCurPage')) {
            $path = (string)$APPLICATION->GetCurPage();
        }

        if ($path === '' && isset($_SERVER['REQUEST_URI'])) {
            $path = (string)parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }

        if ($path === '') {
            $path = '/';
        }

        return $path;
    }
}

if (!function_exists('nlb_seo_context')) {
    /**
     * Собирает SEO-контекст текущей страницы.
     *
     * @return array{title:string,description:string,url:string,image:string,robots:string,site:string}
     */
    function nlb_seo_context(): array
    {
        global $APPLICATION;

        $siteName = defined('SITE_NAME') && SITE_NAME !== '' ? (string)SITE_NAME : 'Библиотека 2026';
        $title = '';
        $description = '';
        $robots = 'index, follow';

        if (is_object($APPLICATION) && method_exists($APPLICATION, 'GetTitle')) {
            $title = trim(strip_tags((string)$APPLICATION->GetTitle(false)));
        }

        if (is_object($APPLICATION) && method_exists($APPLICATION, 'GetPageProperty')) {
            $description = trim(strip_tags((string)$APPLICATION->GetPageProperty('description')));
            $pageRobots = trim((string)$APPLICATION->GetPageProperty('robots'));
            if ($pageRobots !== '') {
                $robots = $pageRobots;
            }
        }

        if ($title === '') {
            $title = $siteName;
        }

        if ($description === '') {
            $description = 'Библиотека 2026 — книги, события, услуги и пространства для работы и учёбы.';
        }

        // Длина сниппета: обрезаем по границе слова, а не по середине.
        if (function_exists('mb_strlen') && mb_strlen($description) > 300) {
            $description = rtrim(mb_substr($description, 0, 297)) . '…';
        }

        $base = nlb_seo_base_url();
        $template = defined('SITE_TEMPLATE_PATH') ? (string)SITE_TEMPLATE_PATH : '';

        return [
            'site' => $siteName,
            'title' => $title,
            'description' => $description,
            'url' => $base . nlb_seo_page_path(),
            'image' => $base . $template . '/assets/images/og-cover.jpg',
            'robots' => $robots,
        ];
    }
}

if (!function_exists('nlb_seo_head')) {
    /**
     * Печатает canonical, robots и карточку для соцсетей.
     * Вызывается из header.php один раз.
     */
    function nlb_seo_head(): void
    {
        $seo = nlb_seo_context();
        $e = 'nlb_seo_escape';

        echo '<link rel="canonical" href="' . $e($seo['url']) . '">' . "\n";

        if ($seo['robots'] !== '') {
            echo '<meta name="robots" content="' . $e($seo['robots']) . '">' . "\n";
        }

        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:site_name" content="' . $e($seo['site']) . '">' . "\n";
        echo '<meta property="og:locale" content="ru_RU">' . "\n";
        echo '<meta property="og:title" content="' . $e($seo['title']) . '">' . "\n";
        echo '<meta property="og:description" content="' . $e($seo['description']) . '">' . "\n";
        echo '<meta property="og:url" content="' . $e($seo['url']) . '">' . "\n";
        echo '<meta property="og:image" content="' . $e($seo['image']) . '">' . "\n";
        echo '<meta property="og:image:width" content="1200">' . "\n";
        echo '<meta property="og:image:height" content="630">' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . $e($seo['title']) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . $e($seo['description']) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . $e($seo['image']) . '">' . "\n";
    }
}

if (!function_exists('nlb_seo_jsonld')) {
    /**
     * Разметка schema.org: библиотека как организация и сайт с поиском.
     * Данные совпадают с контактами в шаблоне — при смене адреса/телефона
     * правьте оба места (footer.php и здесь).
     */
    function nlb_seo_jsonld(): void
    {
        static $printed = false;
        if ($printed) {
            return;
        }
        $printed = true;

        $seo = nlb_seo_context();
        $base = nlb_seo_base_url();
        $template = defined('SITE_TEMPLATE_PATH') ? (string)SITE_TEMPLATE_PATH : '';

        $data = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Library',
                    '@id' => $base . '/#library',
                    'name' => $seo['site'],
                    'url' => $base . '/',
                    'logo' => $base . $template . '/assets/icons/icon-512.png',
                    'image' => $seo['image'],
                    'description' => $seo['description'],
                    'telephone' => '+375-17-300-00-00',
                    'email' => 'info@example.by',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'postalCode' => '220114',
                        'addressCountry' => 'BY',
                        'addressLocality' => 'Минск',
                        'streetAddress' => 'пр. Независимости, 116',
                    ],
                    'openingHoursSpecification' => [
                        [
                            '@type' => 'OpeningHoursSpecification',
                            'dayOfWeek' => [
                                'Monday',
                                'Tuesday',
                                'Wednesday',
                                'Thursday',
                                'Friday',
                                'Saturday',
                                'Sunday',
                            ],
                            'opens' => '10:00',
                            'closes' => '22:00',
                        ],
                    ],
                    'sameAs' => [
                        'https://t.me/example',
                        'https://vk.com/example',
                        'https://youtube.com/@example',
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $base . '/#website',
                    'url' => $base . '/',
                    'name' => $seo['site'],
                    'inLanguage' => 'ru',
                    'publisher' => ['@id' => $base . '/#library'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => $base . '/search/?q={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if ($json === false) {
            return;
        }

        // Закрывающий тег внутри JSON порвал бы <script>.
        $json = str_replace('</', '<\/', $json);

        echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
    }
}
