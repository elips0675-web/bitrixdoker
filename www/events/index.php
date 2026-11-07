<?php
/**
 * События — афиша.
 *
 * Дата начала события хранится в ACTIVE_FROM (дублируется из свойства DATE),
 * поэтому фильтр «предстоящие / прошедшие» и сортировка работают по дате,
 * а не по строке свойства.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$sectionCode = isset($_GET['section']) ? (string)$_GET['section'] : '';
$sectionCode = (string)preg_replace('/[^a-z0-9_-]/i', '', $sectionCode);
$sectionId = $sectionCode !== '' ? nlb_iblock_section_id('events', $sectionCode) : 0;
$past = isset($_GET['past']) && (string)$_GET['past'] === '1';

$now = ConvertTimeStamp(time(), 'FULL');
$GLOBALS['nlbEventsFilter'] = $past
    ? ['<ACTIVE_FROM' => $now]
    : ['>=ACTIVE_FROM' => $now];

if ($sectionId > 0) {
    $GLOBALS['nlbEventsFilter']['SECTION_ID'] = $sectionId;
    $GLOBALS['nlbEventsFilter']['INCLUDE_SUBSECTIONS'] = 'Y';
}

$query = static function (array $params) use ($sectionCode, $past): string {
    if ($sectionCode !== '') {
        $params['section'] = $sectionCode;
    }
    if ($past) {
        $params['past'] = '1';
    }
    return SITE_DIR . 'events/' . ($params ? '?' . http_build_query($params) : '');
};

$APPLICATION->SetTitle($past ? 'Прошедшие события' : 'События');
$APPLICATION->SetPageProperty(
    'description',
    'Афиша библиотеки: лекции, мастер-классы, встречи с авторами и детские занятия.'
);

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--red">Афиша</span>
        <h1 class="page-title"><?= $past ? 'Прошедшие события' : 'События' ?></h1>
        <p class="page-lead">
            Лекции, мастер-классы и встречи с авторами. Вход свободный или по записи —
            смотрите условия в карточке события.
        </p>

        <ul class="section-chips" aria-label="Период">
            <li>
                <a class="section-chip<?= !$past ? ' is-active' : '' ?>"
                   href="<?= SITE_DIR ?>events/">Предстоящие</a>
            </li>
            <li>
                <a class="section-chip<?= $past ? ' is-active' : '' ?>"
                   href="<?= SITE_DIR ?>events/?past=1">Прошедшие</a>
            </li>
        </ul>

        <ul class="section-chips" aria-label="Рубрики событий">
            <li>
                <a class="section-chip<?= $sectionId === 0 ? ' is-active' : '' ?>"
                   href="<?= htmlspecialcharsbx($query([])) ?>">Все рубрики</a>
            </li>
            <?php foreach (nlb_iblock_sections('events') as $section): ?>
                <li>
                    <a class="section-chip<?= $sectionId === $section['ID'] ? ' is-active' : '' ?>"
                       href="<?= htmlspecialcharsbx($query(['section' => $section['CODE']])) ?>">
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
            'IBLOCK_ID' => nlb_iblock_id('events'),
            'NLB_CARD' => 'event',
            'NLB_COLUMNS' => 'col-sm-6 col-lg-4',
            'NEWS_COUNT' => 9,
            'FILTER_NAME' => 'nlbEventsFilter',
            'SORT_BY1' => 'ACTIVE_FROM',
            'SORT_ORDER1' => $past ? 'DESC' : 'ASC',
            'FIELD_CODE' => ['NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL', 'ACTIVE_FROM'],
            'PROPERTY_CODE' => ['DATE', 'PLACE', 'CATEGORY', 'FORMAT', 'SEATS', 'PRICE', 'DURATION', 'REGISTER_URL'],
            'CHECK_DATES' => 'N',
            'DISPLAY_BOTTOM_PAGER' => 'Y',
            'PAGER_TEMPLATE' => '.default',
            'SET_TITLE' => 'N',
            'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
            'ADD_SECTIONS_CHAIN' => 'N',
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => '1800',
        ], false, ['HIDE_ICONS' => 'Y']); ?>
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
                <a class="btn btn-nlb-white btn-lg-nlb" href="<?= SITE_DIR ?>feedback/">Написать нам</a>
                <a class="btn btn-nlb-outline btn-lg-nlb" href="<?= SITE_DIR ?>services/">Услуги и залы</a>
            </div>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
