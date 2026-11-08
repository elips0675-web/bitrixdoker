<?php
/**
 * Новости — список.
 *
 * Рубрики берутся из разделов инфоблока news, поэтому фильтр работает
 * по SECTION_ID, а не по строке запроса.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$sectionCode = isset($_GET['section']) ? (string)$_GET['section'] : '';
$sectionCode = (string)preg_replace('/[^a-z0-9_-]/i', '', $sectionCode);
$sectionId = $sectionCode !== '' ? nlb_iblock_section_id('news', $sectionCode) : 0;

$GLOBALS['nlbNewsFilter'] = $sectionId > 0
    ? ['SECTION_ID' => $sectionId, 'INCLUDE_SUBSECTIONS' => 'Y']
    : [];

$APPLICATION->SetTitle('Новости');
$APPLICATION->SetPageProperty(
    'description',
    'Новости библиотеки: новые поступления, проекты, партнёрства и анонсы мероприятий.'
);
$APPLICATION->AddChainItem('Новости');

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--green">Блог</span>
        <h1 class="page-title">Новости</h1>
        <p class="page-lead">
            Что происходит в библиотеке: новые поступления, цифровые проекты,
            партнёрства и анонсы мероприятий.
        </p>

        <ul class="section-chips" aria-label="Рубрики новостей">
            <li>
                <a class="section-chip<?= $sectionId === 0 ? ' is-active' : '' ?>"
                   href="<?= SITE_DIR ?>news/">Все новости</a>
            </li>
            <?php foreach (nlb_iblock_sections('news') as $section): ?>
                <li>
                    <a class="section-chip<?= $sectionId === $section['ID'] ? ' is-active' : '' ?>"
                       href="<?= SITE_DIR ?>news/?section=<?= urlencode($section['CODE']) ?>">
                        <?= htmlspecialcharsbx($section['NAME']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'nlb_cards', [
            'IBLOCK_TYPE' => 'nlb_content',
            'IBLOCK_ID' => nlb_iblock_id('news'),
            'NLB_CARD' => 'news',
            'NLB_COLUMNS' => 'col-sm-6 col-lg-4',
            'NEWS_COUNT' => 9,
            'FILTER_NAME' => 'nlbNewsFilter',
            'SORT_BY1' => 'ACTIVE_FROM',
            'SORT_ORDER1' => 'DESC',
            'FIELD_CODE' => ['NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'ACTIVE_FROM', 'DETAIL_PAGE_URL'],
            'PROPERTY_CODE' => ['AUTHOR', 'TAGS', 'READ_TIME'],
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

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
