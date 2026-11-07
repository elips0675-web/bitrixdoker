<?php
/**
 * Подписка на рассылку.
 *
 * Бесплатный модуль «Рассылки»: subscribe.form подписывает, subscribe.edit
 * позволяет изменить настройки и отписаться. Настройка — docs/bitrix-integration.md, §9.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Подписка на рассылку');
$APPLICATION->SetPageProperty(
    'description',
    'Подпишитесь на анонсы событий и новости библиотеки. Отписка — в один клик.'
);

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--violet">Рассылка</span>
        <h1 class="page-title">Подписка на рассылку</h1>
        <p class="page-lead">
            Анонсы событий, новые поступления и важные объявления — одно письмо
            в неделю. Без спама, отписка в один клик.
        </p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7" data-reveal>
                <div class="info-card">
                    <h2 class="h4 mb-3">Оформить подписку</h2>
                    <?php $APPLICATION->IncludeComponent('bitrix:subscribe.form', '', [
                        'USE_PERSONALIZATION' => 'Y',
                        'SHOW_HIDDEN' => 'N',
                        'PAGE' => SITE_DIR . 'subscribe/',
                        'CACHE_TYPE' => 'A',
                        'CACHE_TIME' => '3600',
                    ], false, ['HIDE_ICONS' => 'Y']); ?>
                    <p class="form-hint mt-3 mb-0">
                        После отправки придёт письмо с подтверждением — перейдите по ссылке,
                        чтобы подписка заработала (двойное подтверждение).
                    </p>
                </div>
            </div>

            <div class="col-lg-5" data-reveal>
                <div class="info-card mb-4">
                    <h2 class="h5">Что в рассылке</h2>
                    <ul class="mb-0 ps-3">
                        <li>афиша событий на две недели;</li>
                        <li>новые сувениры и подарочные наборы;</li>
                        <li>изменения в работе залов и услуг;</li>
                        <li>сезонные проекты и конкурсы.</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h2 class="h5">Изменить или отменить</h2>
                    <p class="text-muted">
                        Управление подпиской — по e-mail, на который оформлена рассылка.
                    </p>
                    <?php $APPLICATION->IncludeComponent('bitrix:subscribe.edit', '', [
                        'SHOW_HIDDEN' => 'N',
                        'SET_TITLE' => 'N',
                        'CACHE_TYPE' => 'A',
                        'CACHE_TIME' => '3600',
                    ], false, ['HIDE_ICONS' => 'Y']); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
