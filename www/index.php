<?php
/**
 * Главная страница сайта.
 *
 * Контент: hero-карусель (статика), цифры (количество из инфоблоков),
 * услуги, печатные услуги, события, новости, коллекции, партнёры.
 * Все данные — из инфоблоков, созданных миграциями db/.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Библиотека 2026 — центр знаний');
$APPLICATION->SetPageProperty(
    'description',
    'Современная библиотека: книги, цифровые ресурсы, события, услуги и пространства для работы.'
);

$counts = nlb_counts();
$stats = [
    ['value' => $counts['books'], 'label' => 'книг в каталоге', 'link' => SITE_DIR . 'books/'],
    ['value' => $counts['events'], 'label' => 'событий в афише', 'link' => SITE_DIR . 'events/'],
    ['value' => $counts['news'], 'label' => 'новостей', 'link' => SITE_DIR . 'news/'],
    ['value' => $counts['services'], 'label' => 'услуг', 'link' => SITE_DIR . 'services/'],
    ['value' => $counts['printing'], 'label' => 'печатных услуг', 'link' => SITE_DIR . 'printing/'],
    ['value' => $counts['collections'], 'label' => 'подборок', 'link' => SITE_DIR . 'collections/'],
];
?>

<section class="hero" id="top">
    <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="7000">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Слайд 1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Слайд 2"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Слайд 3"></button>
        </div>

        <div class="carousel-inner">
            <div class="carousel-item active">
                <img class="hero-img" src="<?= SITE_TEMPLATE_PATH ?>/assets/images/hero-1.jpg"
                     alt="Современный интерьер библиотеки"
                     fetchpriority="high" decoding="async">
                <div class="hero-overlay"></div>
                <div class="hero-body">
                    <div class="container">
                        <span class="hero-eyebrow">
                            <svg class="i" aria-hidden="true"><use href="#i-sparkles"/></svg>
                            Национальная библиотека · Минск
                        </span>
                        <h1 class="hero-title">Мир знаний нового поколения</h1>
                        <p class="hero-text">Современная библиотека с цифровыми ресурсами, пространствами для работы и событиями каждую неделю.</p>
                        <a class="btn btn-nlb-green btn-lg-nlb btn-icon" href="#services">
                            Начать исследование
                            <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="carousel-item">
                <img class="hero-img" src="<?= SITE_TEMPLATE_PATH ?>/assets/images/hero-2.jpg"
                     alt="Коворкинг в библиотеке"
                     loading="lazy" decoding="async">
                <div class="hero-overlay"></div>
                <div class="hero-body">
                    <div class="container">
                        <span class="hero-eyebrow">
                            <svg class="i" aria-hidden="true"><use href="#i-monitor"/></svg>
                            Коворкинг · Wi-Fi · Компьютерные залы
                        </span>
                        <p class="hero-title">Технологии для вашего роста</p>
                        <p class="hero-text">Коворкинг-зоны, компьютерные залы и высокоскоростной Wi-Fi для продуктивной работы.</p>
                        <a class="btn btn-nlb-green btn-lg-nlb btn-icon" href="<?= SITE_DIR ?>services/">
                            Наши услуги
                            <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="carousel-item">
                <img class="hero-img" src="<?= SITE_TEMPLATE_PATH ?>/assets/images/hero-3.jpg"
                     alt="Лекционный зал"
                     loading="lazy" decoding="async">
                <div class="hero-overlay"></div>
                <div class="hero-body">
                    <div class="container">
                        <span class="hero-eyebrow">
                            <svg class="i" aria-hidden="true"><use href="#i-calendar"/></svg>
                            Лекции · Мастер-классы · Встречи
                        </span>
                        <p class="hero-title">События, которые вдохновляют</p>
                        <p class="hero-text">Лекции, мастер-классы и культурные мероприятия, которые проходят каждую неделю.</p>
                        <a class="btn btn-nlb-green btn-lg-nlb btn-icon" href="<?= SITE_DIR ?>events/">
                            Календарь событий
                            <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="hero-arrow"><svg class="i i-24" aria-hidden="true"><use href="#i-chevron-left"/></svg></span>
            <span class="visually-hidden">Предыдущий слайд</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="hero-arrow"><svg class="i i-24" aria-hidden="true"><use href="#i-chevron-right"/></svg></span>
            <span class="visually-hidden">Следующий слайд</span>
        </button>
    </div>
</section>

<section class="stats-strip" aria-label="Библиотека в цифрах">
    <div class="container">
        <div class="row g-3">
            <?php foreach ($stats as $stat): ?>
                <div class="col-6 col-lg-2">
                    <a class="stat-card" href="<?= htmlspecialcharsbx($stat['link']) ?>">
                        <span class="stat-value"><?= (int)$stat['value'] ?></span>
                        <span class="stat-label"><?= htmlspecialcharsbx($stat['label']) ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="services">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow eyebrow--red">Наши услуги</span>
            <h2 class="section-title">Комфорт и технологии для вашего обучения</h2>
            <p class="section-lead">Пространства и сервисы, где каждый найдёт условия для работы, учёбы и творчества.</p>
        </div>

        <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'nlb_cards', [
            'IBLOCK_TYPE' => 'nlb_services',
            'IBLOCK_ID' => nlb_iblock_id('services'),
            'NLB_CARD' => 'service',
            'NLB_COLUMNS' => 'col-sm-6 col-lg-4',
            'NEWS_COUNT' => 6,
            'SORT_BY1' => 'SORT',
            'SORT_ORDER1' => 'ASC',
            'FIELD_CODE' => ['NAME', 'PREVIEW_TEXT', 'DETAIL_PAGE_URL'],
            'PROPERTY_CODE' => ['ICON', 'COLOR', 'PRICE_FROM', 'DURATION'],
            'SET_TITLE' => 'N',
            'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
            'ADD_SECTIONS_CHAIN' => 'N',
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => '3600',
        ], false, ['HIDE_ICONS' => 'Y']); ?>

        <div class="text-center mt-5">
            <a class="btn btn-nlb-green btn-lg-nlb" href="<?= SITE_DIR ?>services/">Все услуги</a>
        </div>
    </div>
</section>

<section class="section section--soft" id="print">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow eyebrow--violet">Полиграфия</span>
            <h2 class="section-title">Каталог печатных услуг</h2>
            <p class="section-lead">Печать, сканирование и сувениры — от одного экземпляра до крупного тиража.</p>
        </div>

        <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'nlb_cards', [
            'IBLOCK_TYPE' => 'nlb_services',
            'IBLOCK_ID' => nlb_iblock_id('printing'),
            'NLB_CARD' => 'printing',
            'NLB_COLUMNS' => 'col-sm-6 col-xl-3',
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

        <div class="text-center mt-5">
            <a class="btn btn-nlb btn-lg-nlb" href="<?= SITE_DIR ?>printing/">Весь каталог печати</a>
        </div>
    </div>
</section>

<section class="section" id="events">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow eyebrow--green">События</span>
            <h2 class="section-title">Предстоящие мероприятия</h2>
            <p class="section-lead">Лекции, мастер-классы и встречи — вход свободный или по записи.</p>
        </div>

        <?php
        // Только предстоящие: дата начала события хранится в DATE_ACTIVE_FROM.
        $GLOBALS['nlbHomeEventsFilter'] = nlb_events_filter(false);
        $APPLICATION->IncludeComponent('bitrix:news.list', 'nlb_cards', [
            'IBLOCK_TYPE' => 'nlb_content',
            'IBLOCK_ID' => nlb_iblock_id('events'),
            'NLB_CARD' => 'event',
            'NLB_COLUMNS' => 'col-lg-4',
            'NEWS_COUNT' => 3,
            'FILTER_NAME' => 'nlbHomeEventsFilter',
            'SORT_BY1' => 'ACTIVE_FROM',
            'SORT_ORDER1' => 'ASC',
            'FIELD_CODE' => ['NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL', 'ACTIVE_FROM'],
            'PROPERTY_CODE' => ['DATE', 'PLACE', 'CATEGORY', 'FORMAT', 'SEATS', 'PRICE', 'DURATION', 'REGISTER_URL'],
            'CHECK_DATES' => 'N',
            'SET_TITLE' => 'N',
            'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
            'ADD_SECTIONS_CHAIN' => 'N',
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => '1800',
        ], false, ['HIDE_ICONS' => 'Y']); ?>

        <div class="cta-banner mt-5" data-reveal>
            <h2>Не пропустите наши события</h2>
            <p class="text-white-75 mb-4">Подпишитесь на рассылку и получайте уведомления о новых мероприятиях.</p>
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a class="btn btn-nlb-white btn-lg-nlb" href="<?= SITE_DIR ?>subscribe/">Подписаться на рассылку</a>
                <a class="btn btn-nlb-outline btn-lg-nlb" href="<?= SITE_DIR ?>events/">Все события</a>
            </div>
        </div>
    </div>
</section>

<section class="section section--soft" id="news">
    <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4" data-reveal>
            <div>
                <span class="eyebrow eyebrow--green">Блог</span>
                <h2 class="section-title mb-0">Новости</h2>
            </div>
            <a class="btn btn-nlb-outline" href="<?= SITE_DIR ?>news/">Все новости</a>
        </div>

        <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'nlb_cards', [
            'IBLOCK_TYPE' => 'nlb_content',
            'IBLOCK_ID' => nlb_iblock_id('news'),
            'NLB_CARD' => 'news',
            'NLB_COLUMNS' => 'col-sm-6 col-lg-3',
            'NEWS_COUNT' => 4,
            'SORT_BY1' => 'ACTIVE_FROM',
            'SORT_ORDER1' => 'DESC',
            'FIELD_CODE' => ['NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'ACTIVE_FROM', 'DETAIL_PAGE_URL'],
            'PROPERTY_CODE' => ['AUTHOR', 'TAGS', 'READ_TIME'],
            'SET_TITLE' => 'N',
            'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
            'ADD_SECTIONS_CHAIN' => 'N',
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => '3600',
        ], false, ['HIDE_ICONS' => 'Y']); ?>
    </div>
</section>

<section class="section" id="collections">
    <div class="container">
        <div class="section-head section-head--left" data-reveal>
            <span class="eyebrow eyebrow--violet">Сувениры</span>
            <h2 class="section-title">Готовые подарки с символикой библиотеки</h2>
            <p class="section-lead">Шесть наборов на разные поводы: коллегам, детям, в дорогу и на память о Минске.</p>
        </div>

        <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'nlb_cards', [
            'IBLOCK_TYPE' => 'nlb_content',
            'IBLOCK_ID' => nlb_iblock_id('collections'),
            'NLB_CARD' => 'collection',
            'NLB_COLUMNS' => 'col-md-6 col-lg-4',
            'NEWS_COUNT' => 6,
            'SORT_BY1' => 'SORT',
            'SORT_ORDER1' => 'ASC',
            'FIELD_CODE' => ['NAME', 'PREVIEW_TEXT', 'DETAIL_PAGE_URL'],
            'PROPERTY_CODE' => ['COUNT', 'COLOR', 'ICON'],
            'SET_TITLE' => 'N',
            'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
            'ADD_SECTIONS_CHAIN' => 'N',
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => '3600',
        ], false, ['HIDE_ICONS' => 'Y']); ?>

        <div class="text-center mt-5">
            <a class="btn btn-nlb btn-lg-nlb" href="<?= SITE_DIR ?>collections/">Все наборы</a>
        </div>
    </div>
</section>

<section class="partners">
    <div class="container">
        <div class="text-center mb-4" data-reveal>
            <h2 class="text-white h4 mb-1">Нам доверяют</h2>
            <p class="text-white-75 mb-0 small">Партнёры и культурные институции</p>
        </div>
        <div class="row g-3 justify-content-center">
            <div class="col-6 col-md-4 col-lg-2"><div class="partner"><svg class="i" aria-hidden="true"><use href="#i-check"/></svg>Минкультуры</div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner">Белкнига</div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner">БГУ</div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner">НАН Беларуси</div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner">Минскгорисполком</div></div>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
