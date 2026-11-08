<?php
/**
 * Обратная связь.
 *
 * Обращение сохраняется в инфоблок requests штатным
 * bitrix:iblock.element.add.form; письмо NLB_NEW_REQUEST — только уведомление.
 * Настройка — docs/bitrix-integration.md.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$eventXmlId = isset($_GET['event']) ? (string)$_GET['event'] : '';
$eventXmlId = (string)preg_replace('/[^a-z0-9_-]/i', '', $eventXmlId);

$APPLICATION->SetTitle('Обратная связь');
$APPLICATION->AddChainItem('Обратная связь');
$APPLICATION->SetPageProperty(
    'description',
    'Напишите в библиотеку: вопрос о фондах, услугах, событиях или сотрудничестве.'
);

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--green">Написать</span>
        <h1 class="page-title">Обратная связь</h1>
        <p class="page-lead">
            Задайте вопрос, предложите сувенир или расскажите о проблеме.
            Отвечаем в течение рабочего дня.
        </p>
    </div>
</section>

<section class="section pt-0" id="form">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7" data-reveal>
                <div class="info-card">
                    <h2 class="h4 mb-3">Форма сообщения</h2>

                    <?php if ($eventXmlId !== ''): ?>
                        <div class="alert alert-info" role="status">
                            Вы отвечаете на приглашение к событию.
                            Укажите в сообщении, на какое мероприятие регистрируетесь.
                        </div>
                    <?php endif; ?>

                    <?php $APPLICATION->IncludeComponent('bitrix:iblock.element.add.form', 'bootstrap', [
                        'IBLOCK_TYPE' => 'nlb_content',
                        'IBLOCK_ID' => nlb_iblock_id('requests'),
                        'PROPERTY_CODES' => nlb_form_property_codes('request'),
                        'PROPERTY_CODES_REQUIRED' => nlb_form_required_codes('request'),
                        'GROUPS' => [2],
                        'STATUS_NEW' => '',
                        'ELEMENT_ASSOC' => 'NONE',
                        'MAX_USER_ENTRIES' => 0,
                        'USE_CAPTCHA' => 'Y',
                        'SEF_MODE' => 'N',
                        'LIST_URL' => nlb_form_success_url('request'),
                        'SET_TITLE' => 'N',
                        'NLB_FORM' => 'request',
                        'NLB_VALUES' => [
                            'PAGE' => $APPLICATION->GetCurPage(),
                        ],
                    ], false, ['HIDE_ICONS' => 'Y']); ?>

                    <p class="form-hint mt-3 mb-0">
                        Отправляя форму, вы соглашаетесь с
                        <a href="<?= SITE_DIR ?>privacy/">политикой конфиденциальности</a>.
                    </p>
                </div>
            </div>

            <div class="col-lg-5" data-reveal>
                <div class="info-card mb-4">
                    <h2 class="h5">Быстрая связь</h2>
                    <div class="contact-line">
                        <svg class="i" aria-hidden="true"><use href="#i-phone"/></svg>
                        <span><a href="tel:+375173000000">+375 (17) 300-00-00</a></span>
                    </div>
                    <div class="contact-line">
                        <svg class="i" aria-hidden="true"><use href="#i-mail"/></svg>
                        <span><a href="mailto:info@example.by">info@example.by</a></span>
                    </div>
                    <div class="contact-line">
                        <svg class="i" aria-hidden="true"><use href="#i-pin"/></svg>
                        <span>Минск, пр. Независимости, 116</span>
                    </div>
                    <div class="contact-line">
                        <svg class="i" aria-hidden="true"><use href="#i-clock"/></svg>
                        <span>Ежедневно 10:00–22:00</span>
                    </div>
                </div>

                <div class="info-card">
                    <h2 class="h5">Частые вопросы</h2>
                    <ul class="mb-0 ps-3">
                        <li>Как записаться в библиотеку — <a href="<?= SITE_DIR ?>services/">услуги</a>.</li>
                        <li>Где посмотреть сувениры — <a href="<?= SITE_DIR ?>books/">каталог продукции</a>.</li>
                        <li>Как заказать печать — <a href="<?= SITE_DIR ?>printing/">каталог печати</a>.</li>
                        <li>Куда прийти — <a href="<?= SITE_DIR ?>contacts/">контакты</a>.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
