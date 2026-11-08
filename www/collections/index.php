<?php
/**
 * Подарочные наборы — список готовых комплектов сувениров.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Подарочные наборы');
$APPLICATION->SetPageProperty(
    'description',
    'Готовые подарочные наборы сувениров библиотеки: для коллег, дома, детей, путешествий и на память о Минске.'
);
$APPLICATION->AddChainItem('Подарочные наборы');

?>

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
        <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'nlb_cards', [
            'IBLOCK_TYPE' => 'nlb_content',
            'IBLOCK_ID' => nlb_iblock_id('collections'),
            'NLB_CARD' => 'collection',
            'NLB_COLUMNS' => 'col-md-6 col-lg-4',
            'NEWS_COUNT' => 12,
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
                <a class="btn btn-nlb btn-lg-nlb" href="<?= SITE_DIR ?>books/">Перейти в каталог</a>
            </div>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
