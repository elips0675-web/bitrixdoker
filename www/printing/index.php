<?php
/**
 * Каталог печатных услуг — список.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Каталог печатных услуг');
$APPLICATION->SetPageProperty(
    'description',
    'Печать документов, сканирование, переплёт, ламинирование и сувениры: цены и условия.'
);
$APPLICATION->AddChainItem('Каталог печатных услуг');

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--violet">Полиграфия</span>
        <h1 class="page-title">Каталог печатных услуг</h1>
        <p class="page-lead">
            От визиток до широкоформатных баннеров — всё, что нужно для бизнеса
            и творчества. Печать документов, сувениры, фото и фирменный стиль.
        </p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'nlb_cards', [
            'IBLOCK_TYPE' => 'nlb_services',
            'IBLOCK_ID' => nlb_iblock_id('printing'),
            'NLB_CARD' => 'printing',
            'NLB_COLUMNS' => 'col-sm-6 col-xl-3',
            'NEWS_COUNT' => 40,
            'SORT_BY1' => 'SORT',
            'SORT_ORDER1' => 'ASC',
            'FIELD_CODE' => ['NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL'],
            'PROPERTY_CODE' => ['ICON', 'COLOR', 'PRICE_FROM', 'UNIT'],
            'SET_TITLE' => 'N',
            'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
            'ADD_SECTIONS_CHAIN' => 'N',
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => '3600',
        ], false, ['HIDE_ICONS' => 'Y']); ?>

        <p class="form-hint mt-4 mb-0">
            Цены указаны «от» и зависят от объёма, бумаги и срочности.
            Точную стоимость подтвердит сотрудник после расчёта заказа.
        </p>
    </div>
</section>

<section class="section section--soft">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow eyebrow--red">Как заказать</span>
            <h2 class="section-title">От файла до готового тиража</h2>
        </div>

        <div class="row g-4">
            <div class="col-md-4" data-reveal>
                <div class="info-card">
                    <span class="icon-tile icon-tile--blue mb-3">
                        <svg class="i" aria-hidden="true"><use href="#i-file"/></svg>
                    </span>
                    <h3>1. Пришлите файл</h3>
                    <p class="text-muted mb-0">
                        PDF, DOCX или изображение. Проверим макет и подскажем,
                        если нужно поправить поля или шрифты.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-reveal>
                <div class="info-card">
                    <span class="icon-tile icon-tile--green mb-3">
                        <svg class="i" aria-hidden="true"><use href="#i-package"/></svg>
                    </span>
                    <h3>2. Выберите параметры</h3>
                    <p class="text-muted mb-0">
                        Бумага, цветность, двусторонняя печать, переплёт.
                        Сотрудник посчитает итоговую стоимость.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-reveal>
                <div class="info-card">
                    <span class="icon-tile icon-tile--orange mb-3">
                        <svg class="i" aria-hidden="true"><use href="#i-printer"/></svg>
                    </span>
                    <h3>3. Заберите заказ</h3>
                    <p class="text-muted mb-0">
                        Небольшие заказы печатаем в день обращения. О готовности
                        сообщим по телефону или e-mail.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="cta-banner" data-reveal>
            <h2>Нужен расчёт тиража?</h2>
            <p class="text-white-75 mb-4">
                Опишите задачу: количество страниц, экземпляров и сроки —
                ответим с ценой и вариантами материалов.
            </p>
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a class="btn btn-nlb-white btn-lg-nlb" href="<?= SITE_DIR ?>feedback/">Запросить расчёт</a>
                <a class="btn btn-nlb-outline btn-lg-nlb" href="<?= SITE_DIR ?>services/">Другие услуги</a>
            </div>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
