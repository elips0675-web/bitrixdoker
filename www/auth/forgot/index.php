<?php
/**
 * Восстановление пароля.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetPageProperty('robots', 'noindex, follow');

$APPLICATION->SetTitle('Восстановление пароля');
$APPLICATION->SetPageProperty(
    'description',
    'Восстановление пароля к личному кабинету читателя.'
);

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--red">Аккаунт</span>
        <h1 class="page-title">Восстановление пароля</h1>
        <p class="page-lead">
            Укажите e-mail или логин — пришлём ссылку для смены пароля.
        </p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7" data-reveal>
                <div class="info-card">
                    <?php $APPLICATION->IncludeComponent('bitrix:system.auth.forgotpasswd', '', [
                        'AUTH_RESULT' => [],
                    ], false, ['HIDE_ICONS' => 'Y']); ?>

                    <p class="mt-3 mb-0 small">
                        Вспомнили пароль? <a href="<?= SITE_DIR ?>auth/">Войти</a>.
                    </p>
                </div>
            </div>
            <div class="col-lg-5" data-reveal>
                <div class="info-card">
                    <h2 class="h5">Письмо не пришло?</h2>
                    <p class="text-muted mb-0">
                        Проверьте папку «Спам» и правильность адреса. Если письма нет,
                        позвоните: <a href="tel:+375173000000">+375 (17) 300-00-00</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
