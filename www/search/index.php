<?php
/**
 * Поиск по сайту.
 *
 * Форма в шапке отправляет GET ?q=…; параметр стандартный для search.page.
 * arrFILTER — коды инфоблоков с префиксом iblock_ (см. docs/bitrix-integration.md, §10).
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetPageProperty('robots', 'noindex, follow');

$query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$query = mb_substr($query, 0, 200);

$APPLICATION->SetTitle($query !== '' ? 'Поиск: ' . $query : 'Поиск по сайту');
$APPLICATION->SetPageProperty(
    'description',
    'Поиск по книгам, событиям, новостям, услугам и печатному каталогу библиотеки.'
);

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--green">Поиск</span>
        <h1 class="page-title">Поиск по сайту</h1>

        <form class="row g-2 align-items-center mb-3" action="<?= SITE_DIR ?>search/" method="get" role="search" style="max-width: 640px">
            <div class="col">
                <label class="visually-hidden" for="nlbSearchPageInput">Поисковый запрос</label>
                <div class="search-field">
                    <svg class="i i-24" aria-hidden="true"><use href="#i-search"/></svg>
                    <input class="form-control" id="nlbSearchPageInput" type="search" name="q"
                           value="<?= htmlspecialcharsbx($query) ?>"
                           placeholder="Книги, авторы, события, услуги…" autocomplete="off">
                </div>
            </div>
            <div class="col-auto">
                <button class="btn btn-nlb btn-icon" type="submit">
                    Найти
                    <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                </button>
            </div>
        </form>

        <div class="d-flex flex-wrap gap-2" aria-label="Популярные запросы">
            <a class="search-chip" href="<?= SITE_DIR ?>search/?q=каталог">Каталог книг</a>
            <a class="search-chip" href="<?= SITE_DIR ?>search/?q=афиша">Афиша</a>
            <a class="search-chip" href="<?= SITE_DIR ?>search/?q=печать">Печать</a>
            <a class="search-chip" href="<?= SITE_DIR ?>search/?q=коворкинг">Коворкинг</a>
        </div>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <?php if ($query === ''): ?>
            <div class="empty-state">
                Введите запрос — найдём книги, события, новости и услуги.
                Поиск работает по названиям, описаниям и свойствам материалов.
            </div>
        <?php else: ?>
            <?php $APPLICATION->IncludeComponent('bitrix:search.page', '', [
                'RESTART' => 'N',
                'NO_WORD_LOGIC' => 'N',
                'CHECK_DATES' => 'N',
                'USE_TITLE_RANK' => 'N',
                'DEFAULT_SORT' => 'rank',
                'DISPLAY_TOP_PAGER' => 'N',
                'DISPLAY_BOTTOM_PAGER' => 'Y',
                'PAGER_TITLE' => 'Результаты поиска',
                'PAGER_SHOW_ALWAYS' => 'N',
                'PAGER_TEMPLATE' => '',
                'PAGER_DESC_NUMBERING' => 'N',
                'PAGER_DESC_NUMBERING_CACHE_TIME' => '36000',
                'SHOW_LOGIN' => 'N',
                'arrFILTER' => ['iblock_news', 'iblock_events', 'iblock_books', 'iblock_services', 'iblock_printing', 'iblock_collections'],
                'CACHE_TYPE' => 'A',
                'CACHE_TIME' => '3600',
            ], false, ['HIDE_ICONS' => 'Y']); ?>

            <p class="form-hint mt-4 mb-0">
                Если результатов нет, проверьте раскладку и попробуйте более короткий запрос.
                Индексация включается в админке: «Поиск → Переиндексация».
            </p>
        <?php endif; ?>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
