<?php
/**
 * Смена пароля.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetPageProperty('robots', 'noindex, follow');

if (!$USER->IsAuthorized()) {
    LocalRedirect(SITE_DIR . 'auth/');
}

$APPLICATION->SetTitle('Смена пароля');
$APPLICATION->SetPageProperty('description', 'Смена пароля к личному кабинету читателя.');

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--violet">Аккаунт</span>
        <h1 class="page-title">Смена пароля</h1>
        <p class="page-lead">Новый пароль должен содержать не менее 8 символов.</p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="info-card" style="max-width: 720px" data-reveal>
            <?php $APPLICATION->IncludeComponent('bitrix:system.auth.changepasswd', '', [
                'AUTH_RESULT' => [],
            ], false, ['HIDE_ICONS' => 'Y']); ?>

            <p class="mt-3 mb-0 small">
                <a href="<?= SITE_DIR ?>personal/">Вернуться в личный кабинет</a>
            </p>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
