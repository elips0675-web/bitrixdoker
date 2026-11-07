<?php
/**
 * Страница 404.
 *
 * Подключается ядром, если в настройках сайта указан файл 404.php
 * или компонент вызван с SHOW_404 => 'Y'.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetPageProperty('robots', 'noindex, follow');

if (class_exists('CHTTP')) {
    CHTTP::SetStatus('404 Not Found');
}

$APPLICATION->SetTitle('Страница не найдена');
$APPLICATION->SetPageProperty('description', 'Запрошенная страница не найдена.');

?>

<section class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <p class="display-1 fw-bold text-primary mb-0">404</p>
                <h1 class="page-title">Страница не найдена</h1>
                <p class="page-lead mx-auto">
                    Возможно, адрес изменился или страница удалена.
                    Воспользуйтесь поиском или начните с главной.
                </p>

                <form class="row g-2 justify-content-center mb-4" action="<?= SITE_DIR ?>search/" method="get" role="search">
                    <div class="col-sm-8">
                        <label class="visually-hidden" for="nlbNotFoundSearch">Поисковый запрос</label>
                        <div class="search-field">
                            <svg class="i i-24" aria-hidden="true"><use href="#i-search"/></svg>
                            <input class="form-control" id="nlbNotFoundSearch" type="search" name="q"
                                   placeholder="Что вы искали?" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-sm-auto">
                        <button class="btn btn-nlb w-100" type="submit">Найти</button>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a class="btn btn-nlb" href="<?= SITE_DIR ?>">На главную</a>
                    <a class="btn btn-nlb-outline" href="<?= SITE_DIR ?>books/">Каталог книг</a>
                    <a class="btn btn-nlb-outline" href="<?= SITE_DIR ?>sitemap/">Карта сайта</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
