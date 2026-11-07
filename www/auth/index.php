<?php
/**
 * Вход в личный кабинет.
 *
 * Шаблон шапки переключает «Войти» ↔ «Кабинет» по $USER->IsAuthorized().
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetPageProperty('robots', 'noindex, follow');

$APPLICATION->SetTitle('Вход в личный кабинет');
$APPLICATION->SetPageProperty(
    'description',
    'Вход в личный кабинет читателя: продление книг, подписка и история обращений.'
);

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--red">Личный кабинет</span>
        <h1 class="page-title"><?= $USER->IsAuthorized() ? 'Вы уже вошли' : 'Вход в личный кабинет' ?></h1>
        <p class="page-lead">
            В кабинете можно изменить данные профиля, управлять подпиской
            и посмотреть историю обращений.
        </p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7" data-reveal>
                <div class="info-card">
                    <?php if (!$USER->IsAuthorized()): ?>
                        <h2 class="h4 mb-3">Вход</h2>
                        <?php $APPLICATION->IncludeComponent('bitrix:system.auth.authorize', '', [
                            'REGISTER_URL' => SITE_DIR . 'auth/register/',
                            'FORGOT_PASSWORD_URL' => SITE_DIR . 'auth/forgot/',
                            'PROFILE_URL' => SITE_DIR . 'personal/',
                            'SHOW_ERRORS' => 'Y',
                        ], false, ['HIDE_ICONS' => 'Y']); ?>

                        <p class="mt-3 mb-0 small">
                            Нет аккаунта? <a href="<?= SITE_DIR ?>auth/register/">Зарегистрироваться</a>.
                            Забыли пароль? <a href="<?= SITE_DIR ?>auth/forgot/">Восстановить</a>.
                        </p>
                    <?php else: ?>
                        <h2 class="h4 mb-3">Аккаунт</h2>
                        <p class="text-muted">
                            Вы вошли как <strong><?= htmlspecialcharsbx($USER->GetLogin()) ?></strong>.
                        </p>
                        <?php $APPLICATION->IncludeComponent('bitrix:system.auth.form', '', [
                            'REGISTER_URL' => SITE_DIR . 'auth/register/',
                            'FORGOT_PASSWORD_URL' => SITE_DIR . 'auth/forgot/',
                            'PROFILE_URL' => SITE_DIR . 'personal/',
                            'SHOW_ERRORS' => 'Y',
                        ], false, ['HIDE_ICONS' => 'Y']); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5" data-reveal>
                <div class="info-card mb-4">
                    <h2 class="h5">Зачем регистрироваться</h2>
                    <ul class="mb-0 ps-3">
                        <li>продлевать книги онлайн;</li>
                        <li>управлять подпиской на рассылку;</li>
                        <li>хранить историю обращений;</li>
                        <li>записываться на события без повторного ввода данных.</li>
                    </ul>
                </div>
                <div class="info-card">
                    <h2 class="h5">Нужна помощь?</h2>
                    <p class="text-muted mb-0">
                        Если не получается войти, позвоните в справочную:
                        <a href="tel:+375173000000">+375 (17) 300-00-00</a>
                        или напишите через <a href="<?= SITE_DIR ?>feedback/">обратную связь</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
