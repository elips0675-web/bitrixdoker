<?php
/**
 * Сувенир — детальная страница.
 *
 * Заказ оформляется на этой же странице: bitrix:iblock.element.add.form
 * пишет элемент в инфоблок orders, название, артикул и цена подставляются
 * из свойств товара.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$rawCode = isset($_REQUEST['CODE']) ? (string)$_REQUEST['CODE'] : '';
$code = (string)preg_replace('/[^a-z0-9_-]/i', '', $rawCode);
$name = nlb_element_name('books', $code);
$found = $code !== '' && $name !== '';

if (!$found && class_exists('CHTTP')) {
    CHTTP::SetStatus('404 Not Found');
}

$APPLICATION->SetTitle($found ? $name : 'Сувенир не найден');
$APPLICATION->SetPageProperty(
    'description',
    $found ? 'Сувенир библиотеки: ' . $name : 'Запрошенный сувенир не найден.'
);

?>

<section class="section">
    <div class="container">
        <?php if ($found): ?>
            <?php $APPLICATION->IncludeComponent('bitrix:news.detail', 'nlb_detail', [
                'IBLOCK_TYPE' => 'nlb_catalog',
                'IBLOCK_ID' => nlb_iblock_id('books'),
                'ELEMENT_CODE' => $code,
                'FIELD_CODE' => [
                    'NAME',
                    'PREVIEW_TEXT',
                    'DETAIL_TEXT',
                    'PREVIEW_PICTURE',
                    'DETAIL_PICTURE',
                ],
                'PROPERTY_CODE' => [
                    'AUTHOR',
                    'YEAR',
                    'ISBN',
                    'PUBLISHER',
                    'PAGES',
                    'AGE_LIMIT',
                    'AVAILABLE',
                    'SHELF',
                    'PRICE',
                ],
                'SET_TITLE' => 'N',
                'ADD_ELEMENT_CHAIN' => 'Y',
                'SET_STATUS_404' => 'Y',
                'SHOW_404' => 'N',
                'CACHE_TYPE' => 'A',
                'CACHE_TIME' => '3600',
                'NLB_BACK_URL' => SITE_DIR . 'books/',
                'NLB_BACK_TEXT' => 'Сувениры и подарки',
            ], false, ['HIDE_ICONS' => 'Y']); ?>
        <?php else: ?>
            <div class="empty-state">
                Сувенир не найден. Возможно, он снят с продажи или адрес изменился.
                <div class="mt-3">
                    <a class="btn btn-nlb" href="<?= SITE_DIR ?>books/">Сувениры и подарки</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($found): ?>
    <?php
    $orderValues = nlb_element_values('books', $code, ['ISBN', 'PRICE']);
    ?>
    <section class="section section--booking" id="order">
        <div class="container">
            <div class="booking-card">
                <div class="booking-head">
                    <span class="booking-icon">
                        <svg class="i" aria-hidden="true"><use href="#i-package"/></svg>
                    </span>
                    <h2 class="section-title">Заказать «<?= htmlspecialcharsbx($name) ?>»</h2>
                    <p class="section-lead mb-0">
                        Оставьте контакты — подтвердим наличие, способ оплаты и получения.
                    </p>
                </div>

                <?php $APPLICATION->IncludeComponent('bitrix:iblock.element.add.form', 'bootstrap', [
                    'IBLOCK_TYPE' => 'nlb_content',
                    'IBLOCK_ID' => nlb_iblock_id('orders'),
                    'PROPERTY_CODES' => nlb_form_property_codes('order'),
                    'PROPERTY_CODES_REQUIRED' => nlb_form_required_codes('order'),
                    'GROUPS' => [2],
                    'STATUS_NEW' => '',
                    'ELEMENT_ASSOC' => 'NONE',
                    'MAX_USER_ENTRIES' => 0,
                    'USE_CAPTCHA' => 'Y',
                    'SEF_MODE' => 'N',
                    'LIST_URL' => nlb_form_success_url('order'),
                    'SET_TITLE' => 'N',
                    'NLB_FORM' => 'order',
                    'NLB_VALUES' => [
                        'ITEM' => $name,
                        'ARTICLE' => (string)($orderValues['ISBN'] ?? ''),
                        'PRICE' => (string)($orderValues['PRICE'] ?? ''),
                    ],
                ], false, ['HIDE_ICONS' => 'Y']); ?>

                <p class="form-hint mt-3 mb-0">
                    Отправляя форму, вы соглашаетесь с
                    <a href="<?= SITE_DIR ?>privacy/">политикой конфиденциальности</a>.
                </p>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
