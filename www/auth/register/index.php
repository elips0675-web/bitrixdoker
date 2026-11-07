<?php
/**
 * Регистрация читателя.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetPageProperty('robots', 'noindex, follow');

$APPLICATION->SetTitle('Регистрация');
$APPLICATION->SetPageProperty(
    'description',
    'Создание аккаунта читателя: продление книг, подписка и запись на события.'
);

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--green">Аккаунт</span>
        <h1 class="page-title">Регистрация</h1>
        <p class="page-lead">
            Аккаунт нужен для онлайн-продления книг, управления подпиской
            и записи на события. Читательский билет оформляется отдельно.
        </p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7" data-reveal>
                <div class="info-card">
                    <?php $APPLICATION->IncludeComponent('bitrix:system.auth.registration', '', [
                        'SHOW_FIELDS' => ['NAME', 'LAST_NAME', 'EMAIL', 'PERSONAL_PHONE'],
                        'REQUIRED_FIELDS' => ['NAME', 'EMAIL'],
                        'USE_CAPTCHA' => 'Y',
                        'AUTH' => 'Y',
                        'BACKURL' => SITE_DIR . 'personal/',
                    ], false, ['HIDE_ICONS' => 'Y']); ?>

                    <p class="mt-3 mb-0 small">
                        Уже есть аккаунт? <a href="<?= SITE_DIR ?>auth/">Войти</a>.
                    </p>
                </div>
            </div>

            <div class="col-lg-5" data-reveal>
                <div class="info-card">
                    <h2 class="h5">Что нужно знать</h2>
                    <ul class="mb-0 ps-3">
                        <li>Регистрация на сайте не заменяет читательский билет.</li>
                        <li>Указывайте действующий e-mail — на него придёт подтверждение.</li>
                        <li>Данные обрабатываются по <a href="<?= SITE_DIR ?>privacy/">политике конфиденциальности</a>.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
