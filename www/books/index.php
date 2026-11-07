<?php
/**
 * Сувенирная продукция — список.
 *
 * Разделы инфоблока books — категории сувениров, фильтр идёт по SECTION_ID.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$sectionCode = isset($_GET['section']) ? (string)$_GET['section'] : '';
$sectionCode = (string)preg_replace('/[^a-z0-9_-]/i', '', $sectionCode);
$sectionId = $sectionCode !== '' ? nlb_iblock_section_id('books', $sectionCode) : 0;

$GLOBALS['nlbBooksFilter'] = $sectionId > 0
    ? ['SECTION_ID' => $sectionId, 'INCLUDE_SUBSECTIONS' => 'Y']
    : [];

$APPLICATION->SetTitle('Сувенирная продукция');
$APPLICATION->SetPageProperty(
    'description',
    'Сувениры библиотеки: брелоки, фарфор, кружки, одежда, канцелярия, магниты и аксессуары.'
);

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--violet">Сувениры</span>
        <h1 class="page-title">Сувениры и подарки</h1>
        <p class="page-lead">
            Брелоки, фарфор, кружки, одежда, канцелярия, магниты и аксессуары
            для дома и автомобиля. Наличие и цену уточняйте на карточке товара.
        </p>

        <ul class="section-chips" aria-label="Категории">
            <li>
                <a class="section-chip<?= $sectionId === 0 ? ' is-active' : '' ?>"
                   href="<?= SITE_DIR ?>books/">Все сувениры</a>
            </li>
            <?php foreach (nlb_iblock_sections('books') as $section): ?>
                <li>
                    <a class="section-chip<?= $sectionId === $section['ID'] ? ' is-active' : '' ?>"
                       href="<?= SITE_DIR ?>books/?section=<?= urlencode($section['CODE']) ?>">
                        <?= htmlspecialcharsbx($section['NAME']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
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

        <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'nlb_cards', [
            'IBLOCK_TYPE' => 'nlb_catalog',
            'IBLOCK_ID' => nlb_iblock_id('books'),
            'NLB_CARD' => 'book',
            'NLB_COLUMNS' => 'col-sm-6 col-lg-4 col-xl-3',
            'NEWS_COUNT' => 12,
            'FILTER_NAME' => 'nlbBooksFilter',
            'SORT_BY1' => 'SORT',
            'SORT_ORDER1' => 'ASC',
            'SORT_BY2' => 'NAME',
            'SORT_ORDER2' => 'ASC',
            'FIELD_CODE' => ['NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL'],
            'PROPERTY_CODE' => ['AUTHOR', 'YEAR', 'AVAILABLE', 'PRICE'],
            'DISPLAY_BOTTOM_PAGER' => 'Y',
            'PAGER_TEMPLATE' => '.default',
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
        <div class="row g-4 align-items-center">
            <div class="col-lg-7" data-reveal>
                <span class="eyebrow eyebrow--green">Наборы</span>
                <h2 class="section-title">Не знаете, что выбрать?</h2>
                <p class="section-lead mb-0">
                    Собрали готовые комплекты: для коллег, дома, детей,
                    в дорогу и на память о Минске.
                </p>
            </div>
            <div class="col-lg-5 text-lg-end" data-reveal>
                <a class="btn btn-nlb-green btn-lg-nlb btn-icon" href="<?= SITE_DIR ?>collections/">
                    Смотреть наборы
                    <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
