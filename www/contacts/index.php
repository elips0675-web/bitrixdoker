<?php
/**
 * Контакты.
 *
 * Интерактивная карта в объём работ не входит: адрес, схема проезда
 * и способы связи — текстом.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Контакты');
$APPLICATION->AddChainItem('Контакты');
$APPLICATION->SetPageProperty(
    'description',
    'Адрес, телефон, e-mail и часы работы библиотеки. Как добраться и куда обратиться.'
);

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--green">Связаться</span>
        <h1 class="page-title">Контакты</h1>
        <p class="page-lead">
            Приходите, звоните или пишите. Отвечаем на вопросы о фондах, услугах,
            мероприятиях и читательских билетах.
        </p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5" data-reveal>
                <div class="info-card">
                    <h2>Библиотека 2026</h2>
                    <div class="contact-line">
                        <svg class="i" aria-hidden="true"><use href="#i-pin"/></svg>
                        <span>220114, Минск,<br>пр. Независимости, 116</span>
                    </div>
                    <div class="contact-line">
                        <svg class="i" aria-hidden="true"><use href="#i-clock"/></svg>
                        <span>Ежедневно 10:00–22:00<br><span class="small text-muted">Санитарный день — последний вторник месяца</span></span>
                    </div>
                    <div class="contact-line">
                        <svg class="i" aria-hidden="true"><use href="#i-phone"/></svg>
                        <span><a href="tel:+375173000000">+375 (17) 300-00-00</a><br>
                            <span class="small text-muted">Справочная и запись на события</span></span>
                    </div>
                    <div class="contact-line">
                        <svg class="i" aria-hidden="true"><use href="#i-mail"/></svg>
                        <span><a href="mailto:info@example.by">info@example.by</a><br>
                            <span class="small text-muted">Общие вопросы и сотрудничество</span></span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a class="btn btn-nlb" href="<?= SITE_DIR ?>feedback/">Написать нам</a>
                        <button class="btn btn-nlb-outline" type="button"
                                data-bs-toggle="modal" data-bs-target="#requisitesModal">Реквизиты</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-7" data-reveal>
                <div class="info-card mb-4">
                    <h2>Как добраться</h2>
                    <p>
                        Библиотека находится на проспекте Независимости. Вход — со стороны
                        проспекта, рядом остановки общественного транспорта и парковка.
                    </p>
                    <ul class="mb-0">
                        <li>От станции метро — 7–10 минут пешком по проспекту.</li>
                        <li>На автобусе или троллейбусе — до остановки «Библиотека».</li>
                        <li>Для маломобильных посетителей есть пандус и лифт.</li>
                    </ul>
                    <p class="form-hint mt-3 mb-0">
                        Интерактивная карта на сайте не подключена: уточняйте маршрут
                        по телефону справочной.
                    </p>
                </div>

                <div class="row g-4">
                    <div class="col-sm-6">
                        <div class="info-card">
                            <h3>Читателям</h3>
                            <p class="text-muted mb-0">
                                Запись в библиотеку, продление книг, доступ к электронным ресурсам.
                            </p>
                            <a class="stretched-link small fw-semibold" href="<?= SITE_DIR ?>services/">Услуги</a>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-card">
                            <h3>События</h3>
                            <p class="text-muted mb-0">
                                Запись на лекции и мастер-классы, проведение своего мероприятия.
                            </p>
                            <a class="stretched-link small fw-semibold" href="<?= SITE_DIR ?>events/">Афиша</a>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-card">
                            <h3>Печать</h3>
                            <p class="text-muted mb-0">
                                Расчёт тиража, печать документов, сканирование и переплёт.
                            </p>
                            <a class="stretched-link small fw-semibold" href="<?= SITE_DIR ?>printing/">Каталог печати</a>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-card">
                            <h3>Подписка</h3>
                            <p class="text-muted mb-0">
                                Анонсы событий и новости библиотеки — на e-mail.
                            </p>
                            <a class="stretched-link small fw-semibold" href="<?= SITE_DIR ?>subscribe/">Подписаться</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
