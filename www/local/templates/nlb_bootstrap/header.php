<?php
/**
 * NLB Bootstrap — header.php
 * Шаблон сайта для 1С-Битрикс: Управление сайтом.
 *
 * @var CMain $APPLICATION
 * @var CUser $USER
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$nlbSiteDir = SITE_DIR;
$nlbTpl = SITE_TEMPLATE_PATH;
$nlbAuthorized = $USER->IsAuthorized();
?>
<!DOCTYPE html>
<html lang="<?= LANGUAGE_ID ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1220">

    <?php $APPLICATION->ShowHead(); ?>
    <title><?php $APPLICATION->ShowTitle(); ?></title>

    <?php /* canonical, robots, Open Graph, Twitter Card, schema.org */ ?>
    <?php nlb_seo_head(); ?>
    <?php nlb_seo_jsonld(); ?>

    <link rel="manifest" href="<?= $nlbSiteDir ?>manifest.webmanifest">
    <link rel="icon" href="<?= $nlbTpl ?>/assets/icons/icon.svg" type="image/svg+xml">
    <link rel="icon" href="<?= $nlbTpl ?>/assets/icons/favicon-32.png" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="<?= $nlbTpl ?>/assets/icons/apple-touch-icon.png" sizes="180x180">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Библиотека">

    <?php /* Шрифты — системные: внешний запрос к Google Fonts убран (скорость, приватность, офлайн) */ ?>
    <link rel="preload" href="<?= $nlbTpl ?>/styles.css" as="style">
    <link rel="stylesheet" href="<?= $nlbTpl ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $nlbTpl ?>/styles.css">
</head>
<body>
<?php $APPLICATION->ShowPanel(); ?>

<?php /* Inline SVG-спрайт: иконки не тянут внешние шрифты и работают офлайн. */ ?>
<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute;overflow:hidden" aria-hidden="true" focusable="false">
    <symbol id="i-book" viewBox="0 0 24 24"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3Z"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
    <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></symbol>
    <symbol id="i-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></symbol>
    <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></symbol>
    <symbol id="i-arrow-right" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></symbol>
    <symbol id="i-chevron-left" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></symbol>
    <symbol id="i-chevron-right" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></symbol>
    <symbol id="i-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 11h18"/></symbol>
    <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
    <symbol id="i-pin" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></symbol>
    <symbol id="i-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></symbol>
    <symbol id="i-wifi" viewBox="0 0 24 24"><path d="M2 8a16 16 0 0 1 20 0"/><path d="M5 12a11 11 0 0 1 14 0"/><path d="M8.5 15.5a6 6 0 0 1 7 0"/><circle cx="12" cy="19" r="1"/></symbol>
    <symbol id="i-cup" viewBox="0 0 24 24"><path d="M4 8h12v6a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V8Z"/><path d="M16 9h2a3 3 0 0 1 0 6h-2"/><path d="M7 2v2M11 2v2"/></symbol>
    <symbol id="i-monitor" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></symbol>
    <symbol id="i-printer" viewBox="0 0 24 24"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></symbol>
    <symbol id="i-scan" viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"/></symbol>
 <symbol id="i-mic" viewBox="0 0 24 24"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><path d="M12 19v3"/></symbol>
 <symbol id="i-expand" viewBox="0 0 24 24"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></symbol>
 <symbol id="i-collapse" viewBox="0 0 24 24"><path d="M4 14h6v6"/><path d="M20 10h-6V4"/><path d="m14 10 7-7"/><path d="m3 21 7-7"/></symbol>
 <symbol id="i-thumb-up" viewBox="0 0 24 24"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></symbol>
 <symbol id="i-thumb-down" viewBox="0 0 24 24"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"/></symbol>
    <symbol id="i-star" viewBox="0 0 24 24"><path d="m12 3 2.6 5.6 6.1.8-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.4l6.1-.8L12 3Z"/></symbol>
    <symbol id="i-phone" viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z"/></symbol>
    <symbol id="i-mail" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="m3 7 9 6 9-6"/></symbol>
    <symbol id="i-send" viewBox="0 0 24 24"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/></symbol>
    <symbol id="i-chat" viewBox="0 0 24 24"><path d="M21 12a8.5 8.5 0 0 1-12.3 7.6L3 21l1.4-5.2A8.5 8.5 0 1 1 21 12Z"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
    <symbol id="i-up" viewBox="0 0 24 24"><path d="m6 14 6-6 6 6"/></symbol>
    <symbol id="i-whatsapp" viewBox="0 0 24 24"><path d="M21 12a9 9 0 0 1-13.4 7.9L3 21l1.2-4.5A9 9 0 1 1 21 12Z"/><path d="M8.5 8.5c0 4 3 7 7 7 .7 0 1.5-.6 1.5-1.3 0-.4-.2-.7-.5-.9l-1.6-.8c-.3-.1-.6 0-.8.2l-.5.6c-.4-.2-1.6-1.4-1.8-1.8l.6-.5c.2-.2.3-.5.2-.8l-.8-1.6c-.2-.3-.5-.5-.9-.5-.7 0-1.4.8-1.4 1.5Z"/></symbol>
    <symbol id="i-facebook" viewBox="0 0 24 24"><path d="M15 3h-3a4 4 0 0 0-4 4v3H5v4h3v7h4v-7h3l1-4h-4V7a1 1 0 0 1 1-1h2V3Z"/></symbol>
    <symbol id="i-instagram" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></symbol>
    <symbol id="i-youtube" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3V9Z"/></symbol>
    <symbol id="i-card" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></symbol>
    <symbol id="i-image" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></symbol>
    <symbol id="i-package" viewBox="0 0 24 24"><path d="m12 3 9 5v8l-9 5-9-5V8l9-5Z"/><path d="m3 8 9 5 9-5"/><path d="M12 13v10"/></symbol>
    <symbol id="i-laptop" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/></symbol>
    <symbol id="i-headphones" viewBox="0 0 24 24"><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="7" rx="2"/><rect x="17" y="14" width="4" height="7" rx="2"/></symbol>
    <symbol id="i-globe" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18Z"/></symbol>
    <symbol id="i-sparkles" viewBox="0 0 24 24"><path d="m12 3 1.8 4.2L18 9l-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8L12 3Z"/><path d="m5 15 .9 2.1L8 18l-2.1.9L5 21l-.9-2.1L2 18l2.1-.9L5 15Z"/></symbol>
<symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></symbol>
<symbol id="i-flame" viewBox="0 0 24 24"><path d="M12 2c1.5 4 5 6 5 10a5 5 0 0 1-10 0c0-2 1-3.5 2.5-5 .5 1 1 1.5 2 2 0-3-.5-5 .5-7Z"/></symbol>
<symbol id="i-music" viewBox="0 0 24 24"><path d="M9 18V5l10-2v12"/><circle cx="6" cy="18" r="3"/><circle cx="16" cy="15" r="3"/></symbol>
<symbol id="i-dumbbell" viewBox="0 0 24 24"><path d="M4 9v6M7 7v10M17 7v10M20 9v6M7 12h10"/></symbol>
    <symbol id="i-file" viewBox="0 0 24 24"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7l-5-5Z"/><path d="M14 2v5h5"/><path d="M9 13h6M9 17h6"/></symbol>
    <symbol id="i-cookie" viewBox="0 0 24 24"><path d="M12 3a9 9 0 1 0 9 9 4.5 4.5 0 0 1-5.2-5.2A4.5 4.5 0 0 1 12 3Z"/><circle cx="9" cy="9.5" r="1"/><circle cx="8.5" cy="14.5" r="1"/><circle cx="13" cy="15" r="1"/><circle cx="15" cy="10.5" r="1"/></symbol>
</svg>

<header class="site-header" id="siteHeader">
    <div class="topbar d-none d-lg-block">
        <div class="container d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="d-inline-flex align-items-center gap-2">
                    <svg class="i"><use href="#i-pin"/></svg>
                    Минск, пр. Независимости, 116
                </span>
                <span class="sep"></span>
                <span class="d-inline-flex align-items-center gap-2">
                    <svg class="i"><use href="#i-clock"/></svg>
                    Ежедневно 10:00–22:00
                </span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="tel:+375173000000" class="d-inline-flex align-items-center gap-2">
                    <svg class="i"><use href="#i-phone"/></svg>
                    +375 (17) 300-00-00
                </a>
                <span class="sep"></span>
                <a href="mailto:info@example.by">info@example.by</a>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-nlb" aria-label="Основная навигация">
        <div class="container">
            <a class="brand" href="<?= $nlbSiteDir ?>">
                <span class="brand-mark">
                    <svg class="i" aria-hidden="true"><use href="#i-book"/></svg>
                </span>
                <span>
                    <span class="brand-name">Библиотека 2026</span>
                    <span class="brand-tag">Центр знаний</span>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nlbNav"
                    aria-controls="nlbNav" aria-expanded="false" aria-label="Открыть меню">
                <svg class="i i-28" aria-hidden="true"><use href="#i-menu"/></svg>
            </button>

            <div class="collapse navbar-collapse" id="nlbNav">
                <?php $APPLICATION->IncludeComponent(
                    'bitrix:menu',
                    'top_bootstrap',
                    [
                        'ROOT_MENU_TYPE' => 'top',
                        'MAX_LEVEL' => '2',
                        'CHILD_MENU_TYPE' => 'left',
                        'USE_EXT' => 'Y',
                        'DELAY' => 'N',
                        'ALLOW_MULTI_SELECT' => 'N',
                        'MENU_CACHE_TYPE' => 'A',
                        'MENU_CACHE_TIME' => '3600',
                        'MENU_CACHE_USE_GROUPS' => 'Y',
                        'MENU_CACHE_GET_VARS' => [],
                    ],
                    false,
                    ['HIDE_ICONS' => 'Y']
                ); ?>

                <div class="header-actions ms-lg-3">
                    <button class="header-icon" type="button" data-bs-toggle="modal" data-bs-target="#searchModal"
                            aria-label="Поиск по сайту">
                        <svg class="i" aria-hidden="true"><use href="#i-search"/></svg>
                    </button>

                    <a class="header-icon" href="<?= $nlbSiteDir ?>personal/" aria-label="Уведомления и личный кабинет">
                        <svg class="i" aria-hidden="true"><use href="#i-bell"/></svg>
                        <?php if ($nlbAuthorized): ?>
                            <span class="header-dot"></span>
                        <?php endif; ?>
                    </a>

                    <a class="header-icon" href="<?= $nlbSiteDir ?><?= $nlbAuthorized ? 'personal/' : 'auth/' ?>"
                       aria-label="<?= $nlbAuthorized ? 'Личный кабинет' : 'Вход в личный кабинет' ?>">
                        <svg class="i" aria-hidden="true"><use href="#i-user"/></svg>
                    </a>

                    <?php if ($nlbAuthorized): ?>
                        <a class="btn btn-nlb btn-sm-nlb ms-1" href="<?= $nlbSiteDir ?>personal/">Кабинет</a>
                    <?php else: ?>
                        <a class="btn btn-nlb btn-sm-nlb ms-1" href="<?= $nlbSiteDir ?>auth/">Войти</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>

<div class="modal fade modal-nlb" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="searchModalLabel">Поиск по сайту</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form action="<?= $nlbSiteDir ?>search/" method="get" role="search"
                      data-search-endpoint="<?= $nlbSiteDir ?>local/ajax/search.php">
                    <label class="visually-hidden" for="nlbSearchInput">Поисковый запрос</label>
                    <div class="search-field mb-3">
                        <svg class="i i-24" aria-hidden="true"><use href="#i-search"/></svg>
                        <input class="form-control" id="nlbSearchInput" type="search" name="q"
                               placeholder="Сувениры, события, услуги…" autocomplete="off">
                    </div>
                    <div class="search-instant" id="nlbSearchInstant" aria-live="polite" hidden></div>
                    <div class="d-flex flex-wrap gap-2 mb-3" aria-label="Популярные запросы">
                        <a class="search-chip" href="<?= $nlbSiteDir ?>search/?q=сувенир">Сувениры</a>
                        <a class="search-chip" href="<?= $nlbSiteDir ?>search/?q=набор">Подарочные наборы</a>
                        <a class="search-chip" href="<?= $nlbSiteDir ?>search/?q=афиша">Афиша</a>
                        <a class="search-chip" href="<?= $nlbSiteDir ?>search/?q=печать">Печать</a>
                    </div>
                    <button class="btn btn-nlb w-100 btn-icon" type="submit">
                        Найти
                        <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                    </button>
                </form>
                <p class="form-hint mt-3 mb-0">Подсказка: <kbd>Ctrl</kbd> + <kbd>K</kbd> открывает поиск с любой страницы.</p>
            </div>
        </div>
    </div>
</div>

<?php if ($APPLICATION->GetCurPage(false) !== $nlbSiteDir): ?>
    <div class="container page-head">
        <?php $APPLICATION->IncludeComponent(
            'bitrix:breadcrumb',
            'bootstrap',
            [
                'START_FROM' => '0',
                'PATH' => '',
                'SITE_ID' => SITE_ID,
            ],
            false
        ); ?>
    </div>
<?php endif; ?>

<main class="site-main" id="main">
