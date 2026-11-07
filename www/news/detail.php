<?php
/**
 * Новость — детальная страница.
 *
 * Код элемента приходит из ЧПУ (/news/<код>/) или из detail.php?CODE=<код>.
 * Название запрашивается после пролога: в <title> оно попадает через
 * отложенный вывод ShowTitle().
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$rawCode = isset($_REQUEST['CODE']) ? (string)$_REQUEST['CODE'] : '';
$code = (string)preg_replace('/[^a-z0-9_-]/i', '', $rawCode);
$name = nlb_element_name('news', $code);
$found = $code !== '' && $name !== '';

if (!$found && class_exists('CHTTP')) {
    CHTTP::SetStatus('404 Not Found');
}

$APPLICATION->SetTitle($found ? $name : 'Новость не найдена');
$APPLICATION->SetPageProperty(
    'description',
    $found ? 'Новость библиотеки: ' . $name : 'Запрошенная новость не найдена.'
);

?>

<section class="section">
    <div class="container">
        <?php if ($found): ?>
            <?php $APPLICATION->IncludeComponent('bitrix:news.detail', 'nlb_detail', [
                'IBLOCK_TYPE' => 'nlb_content',
                'IBLOCK_ID' => nlb_iblock_id('news'),
                'ELEMENT_CODE' => $code,
                'FIELD_CODE' => [
                    'NAME',
                    'PREVIEW_TEXT',
                    'DETAIL_TEXT',
                    'PREVIEW_PICTURE',
                    'DETAIL_PICTURE',
                    'ACTIVE_FROM',
                    'DATE_CREATE',
                ],
                'PROPERTY_CODE' => ['AUTHOR', 'TAGS', 'READ_TIME', 'SOURCE_URL'],
                'SET_TITLE' => 'N',
                'ADD_ELEMENT_CHAIN' => 'Y',
                'SET_STATUS_404' => 'Y',
                'SHOW_404' => 'N',
                'CACHE_TYPE' => 'A',
                'CACHE_TIME' => '3600',
                'NLB_BACK_URL' => SITE_DIR . 'news/',
                'NLB_BACK_TEXT' => 'Все новости',
            ], false, ['HIDE_ICONS' => 'Y']); ?>
        <?php else: ?>
            <div class="empty-state">
                Новость не найдена. Возможно, она удалена или адрес изменился.
                <div class="mt-3">
                    <a class="btn btn-nlb" href="<?= SITE_DIR ?>news/">Все новости</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
