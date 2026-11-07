<?php
/**
 * Карта сайта — человеческая, со ссылками на все публичные разделы.
 *
 * XML-карта для поисковиков настраивается отдельно в главном модуле.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Карта сайта');
$APPLICATION->SetPageProperty('description', 'Все разделы сайта библиотеки в одном списке.');

$sections = [
    'Основное' => [
        ['Главная', SITE_DIR],
        ['О библиотеке', SITE_DIR . 'about/'],
        ['Новости', SITE_DIR . 'news/'],
        ['События', SITE_DIR . 'events/'],
        ['Подарочные наборы', SITE_DIR . 'collections/'],
        ['Контакты', SITE_DIR . 'contacts/'],
    ],
    'Каталоги и услуги' => [
        ['Каталог продукции', SITE_DIR . 'books/'],
        ['Услуги', SITE_DIR . 'services/'],
        ['Каталог печатных услуг', SITE_DIR . 'printing/'],
        ['Поиск по сайту', SITE_DIR . 'search/'],
    ],
    'Сервисы' => [
        ['Обратная связь', SITE_DIR . 'feedback/'],
        ['Подписка на рассылку', SITE_DIR . 'subscribe/'],
        ['Вход в личный кабинет', SITE_DIR . 'auth/'],
        ['Регистрация', SITE_DIR . 'auth/register/'],
        ['Личный кабинет', SITE_DIR . 'personal/'],
    ],
    'Правовая информация' => [
        ['Правила пользования', SITE_DIR . 'rules/'],
        ['Политика конфиденциальности', SITE_DIR . 'privacy/'],
        ['Условия использования', SITE_DIR . 'terms/'],
        ['Карта сайта', SITE_DIR . 'sitemap/'],
    ],
];

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--violet">Навигация</span>
        <h1 class="page-title">Карта сайта</h1>
        <p class="page-lead">
            Все публичные разделы. Интерактивная карта в этот список не входит —
            она не подключена на сайте.
        </p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($sections as $title => $links): ?>
                <div class="col-sm-6 col-lg-3" data-reveal>
                    <div class="info-card">
                        <h2 class="h5"><?= htmlspecialcharsbx($title) ?></h2>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($links as [$text, $href]): ?>
                                <li class="mb-2">
                                    <a href="<?= htmlspecialcharsbx($href) ?>"><?= htmlspecialcharsbx($text) ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
