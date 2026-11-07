<?php
/**
 * Печатная услуга — детальная страница.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$rawCode = isset($_REQUEST['CODE']) ? (string)$_REQUEST['CODE'] : '';
$code = (string)preg_replace('/[^a-z0-9_-]/i', '', $rawCode);
$name = nlb_element_name('printing', $code);
$found = $code !== '' && $name !== '';

if (!$found && class_exists('CHTTP')) {
    CHTTP::SetStatus('404 Not Found');
}

$APPLICATION->SetTitle($found ? $name : 'Печатная услуга не найдена');
$APPLICATION->SetPageProperty(
    'description',
    $found ? 'Печатная услуга: ' . $name : 'Запрошенная печатная услуга не найдена.'
);

?>

<section class="section">
    <div class="container">
        <?php if ($found): ?>
            <?php $APPLICATION->IncludeComponent('bitrix:news.detail', 'nlb_detail', [
                'IBLOCK_TYPE' => 'nlb_services',
                'IBLOCK_ID' => nlb_iblock_id('printing'),
                'ELEMENT_CODE' => $code,
                'FIELD_CODE' => [
                    'NAME',
                    'PREVIEW_TEXT',
                    'DETAIL_TEXT',
                    'PREVIEW_PICTURE',
                    'DETAIL_PICTURE',
                ],
                'PROPERTY_CODE' => ['ICON', 'COLOR', 'PRICE_FROM', 'UNIT'],
                'SET_TITLE' => 'N',
                'ADD_ELEMENT_CHAIN' => 'Y',
                'SET_STATUS_404' => 'Y',
                'SHOW_404' => 'N',
                'CACHE_TYPE' => 'A',
                'CACHE_TIME' => '3600',
                'NLB_BACK_URL' => SITE_DIR . 'printing/',
                'NLB_BACK_TEXT' => 'Каталог печати',
            ], false, ['HIDE_ICONS' => 'Y']); ?>
        <?php else: ?>
            <div class="empty-state">
                Услуга не найдена. Откройте каталог — там все варианты печати и обработки.
                <div class="mt-3">
                    <a class="btn btn-nlb" href="<?= SITE_DIR ?>printing/">Каталог печати</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
