<?php
/**
 * О библиотеке.
 *
 * Цифры — только количество материалов из инфоблоков (nlb_counts()).
 * Просмотров и других счётчиков в структуре нет.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$counts = nlb_counts();

$APPLICATION->SetTitle('О библиотеке');
$APPLICATION->SetPageProperty(
    'description',
    'Библиотека 2026 — современное пространство для чтения, работы и встреч: фонды, услуги, события.'
);

?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--red">О нас</span>
        <h1 class="page-title">Библиотека как пространство</h1>
        <p class="page-lead">
            Мы соединяем книги, цифровые ресурсы и живые встречи. Здесь можно
            читать в тишине, работать в коворкинге, учиться и находить людей
            по интересам.
        </p>
    </div>
</section>

<section class="stats-strip" aria-label="Библиотека в цифрах">
    <div class="container">
        <div class="row g-3">
            <div class="col-6 col-lg-2">
                <a class="stat-card" href="<?= SITE_DIR ?>books/">
                    <span class="stat-value"><?= (int)$counts['books'] ?></span>
                    <span class="stat-label">книг в каталоге</span>
                </a>
            </div>
            <div class="col-6 col-lg-2">
                <a class="stat-card" href="<?= SITE_DIR ?>events/">
                    <span class="stat-value"><?= (int)$counts['events'] ?></span>
                    <span class="stat-label">событий в афише</span>
                </a>
            </div>
            <div class="col-6 col-lg-2">
                <a class="stat-card" href="<?= SITE_DIR ?>news/">
                    <span class="stat-value"><?= (int)$counts['news'] ?></span>
                    <span class="stat-label">новостей</span>
                </a>
            </div>
            <div class="col-6 col-lg-2">
                <a class="stat-card" href="<?= SITE_DIR ?>services/">
                    <span class="stat-value"><?= (int)$counts['services'] ?></span>
                    <span class="stat-label">услуг</span>
                </a>
            </div>
            <div class="col-6 col-lg-2">
                <a class="stat-card" href="<?= SITE_DIR ?>printing/">
                    <span class="stat-value"><?= (int)$counts['printing'] ?></span>
                    <span class="stat-label">печатных услуг</span>
                </a>
            </div>
            <div class="col-6 col-lg-2">
                <a class="stat-card" href="<?= SITE_DIR ?>collections/">
                    <span class="stat-value"><?= (int)$counts['collections'] ?></span>
                    <span class="stat-label">подборок</span>
                </a>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4" data-reveal>
                <div class="info-card">
                    <span class="icon-tile icon-tile--blue mb-3">
                        <svg class="i" aria-hidden="true"><use href="#i-book"/></svg>
                    </span>
                    <h2>Фонды</h2>
                    <p class="text-muted mb-0">
                        Художественная и научная литература, детские книги, периодика
                        и электронные ресурсы. Каталог — с автором, годом и шифром.
                    </p>
                </div>
            </div>
            <div class="col-lg-4" data-reveal>
                <div class="info-card">
                    <span class="icon-tile icon-tile--green mb-3">
                        <svg class="i" aria-hidden="true"><use href="#i-users"/></svg>
                    </span>
                    <h2>Пространства</h2>
                    <p class="text-muted mb-0">
                        Читальные залы, коворкинг, компьютерные места и залы для
                        лекций. Часть пространств доступна по предварительной записи.
                    </p>
                </div>
            </div>
            <div class="col-lg-4" data-reveal>
                <div class="info-card">
                    <span class="icon-tile icon-tile--orange mb-3">
                        <svg class="i" aria-hidden="true"><use href="#i-sparkles"/></svg>
                    </span>
                    <h2>События</h2>
                    <p class="text-muted mb-0">
                        Лекции, мастер-классы, книжные клубы и встречи с авторами.
                        Афиша обновляется, запись — на странице события.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--soft">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-7" data-reveal>
                <span class="eyebrow eyebrow--violet">Принципы</span>
                <h2 class="section-title">Как мы работаем</h2>
                <div class="bitrix-content">
                    <p>
                        Библиотека открыта для всех: читательский билет оформляется
                        бесплатно, а основные пространства доступны без записи.
                    </p>
                    <ul>
                        <li><strong>Доступность.</strong> Пандусы, лифты и понятная навигация.</li>
                        <li><strong>Открытость.</strong> События и подборки — бесплатно или по записи.</li>
                        <li><strong>Технологии.</strong> Wi-Fi, компьютеры, цифровые ресурсы и печать.</li>
                        <li><strong>Люди.</strong> Библиотекари помогают с поиском и подбором книг.</li>
                    </ul>
                    <p>
                        Если нужной книги нет в каталоге — спросите на стойке информации:
                        подскажем, где её найти, или оформим заказ по межбиблиотечному обмену.
                    </p>
                </div>
            </div>
            <div class="col-lg-5" data-reveal>
                <div class="info-card">
                    <h2>Партнёры</h2>
                    <p class="text-muted">
                        Культурные институции и образовательные организации, вместе
                        с которыми мы проводим события и пополняем фонды.
                    </p>
                    <ul class="mb-0">
                        <li>Министерство культуры</li>
                        <li>Белкнига</li>
                        <li>БГУ</li>
                        <li>НАН Беларуси</li>
                        <li>Минскгорисполком</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a class="btn btn-nlb btn-lg-nlb" href="<?= SITE_DIR ?>contacts/">Связаться с нами</a>
        </div>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
