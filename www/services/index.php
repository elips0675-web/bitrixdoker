<?php
/**
 * Услуги библиотеки — список.
 *
 * Сетка премиальных услуг (карточки с иконкой, ценой и кнопками), превью
 * каталога печати, три информационные карточки и финальный CTA.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Услуги');
$APPLICATION->SetPageProperty(
    'description',
    'Премиальные услуги библиотеки: обзорная площадка, сауна, кафе, лекторий, концертный зал, детская комната, спортзал и коворкинг.'
);

?>

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
        <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'nlb_cards', [
            'IBLOCK_TYPE' => 'nlb_services',
            'IBLOCK_ID' => nlb_iblock_id('services'),
            'NLB_CARD' => 'service-grid',
            'NEWS_COUNT' => 12,
            'SORT_BY1' => 'SORT',
            'SORT_ORDER1' => 'ASC',
            'FIELD_CODE' => ['NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL'],
            'PROPERTY_CODE' => ['ICON', 'COLOR', 'SUBTITLE', 'PRICE_FROM', 'PRICE_LABEL', 'FEATURES'],
            'SET_TITLE' => 'N',
            'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
            'ADD_SECTIONS_CHAIN' => 'N',
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => '3600',
        ], false, ['HIDE_ICONS' => 'Y']); ?>
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

        <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'nlb_cards', [
            'IBLOCK_TYPE' => 'nlb_catalog',
            'IBLOCK_ID' => nlb_iblock_id('printing'),
            'NLB_CARD' => 'printing',
            'NLB_COLUMNS' => 'col-6 col-lg-3',
            'NEWS_COUNT' => 8,
            'SORT_BY1' => 'SORT',
            'SORT_ORDER1' => 'ASC',
            'FIELD_CODE' => ['NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL'],
            'PROPERTY_CODE' => ['ICON', 'COLOR', 'PRICE_FROM', 'UNIT'],
            'SET_TITLE' => 'N',
            'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
            'ADD_SECTIONS_CHAIN' => 'N',
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => '3600',
        ], false, ['HIDE_ICONS' => 'Y']); ?>

        <div class="text-center mt-5" data-reveal>
            <a class="btn btn-nlb-green btn-lg-nlb btn-icon" href="<?= SITE_DIR ?>printing/">
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
                <a class="btn btn-nlb-white btn-lg-nlb" href="<?= SITE_DIR ?>services/#services-list">
                    Забронировать сейчас
                </a>
                <a class="btn btn-nlb-outline btn-lg-nlb" href="<?= SITE_DIR ?>feedback/">
                    Связаться с нами
                </a>
            </div>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
