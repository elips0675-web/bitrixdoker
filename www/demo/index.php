<?php
/**
 * Демо-лендинг шаблона NLB Bootstrap.
 * Скопируйте файл в корень сайта как /index.php (или в раздел /landing/).
 * Требуется установленный шаблон nlb_bootstrap (см. docs/installation.md).
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Библиотека 2026 — центр знаний');
$APPLICATION->SetPageProperty('description', 'Современная библиотека: книги, цифровые ресурсы, события, услуги и пространства для работы.');
?>
<section class="hero" id="top">
        <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="7000">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Слайд 1"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Слайд 2"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Слайд 3"></button>
            </div>

            <div class="carousel-inner">
                <div class="carousel-item active">
                    <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/hero-1.jpg', 'Современный интерьер библиотеки', ['class' => 'hero-img', 'sizes' => '100vw', 'priority' => true]) ?>
                    <div class="hero-overlay"></div>
                    <div class="hero-body">
                        <div class="container">
                            <span class="hero-eyebrow">
                                <svg class="i" aria-hidden="true"><use href="#i-sparkles"/></svg>
                                Национальная библиотека · Минск
                            </span>
                            <h1 class="hero-title">Мир знаний нового поколения</h1>
                            <p class="hero-text">Современная библиотека с более чем 500 000 книг, цифровыми ресурсами и инновационными пространствами.</p>
                            <a class="btn btn-nlb-green btn-lg-nlb btn-icon" href="/services/">
                                Начать исследование
                                <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="carousel-item">
                    <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/hero-2.jpg', 'Коворкинг в библиотеке', ['class' => 'hero-img', 'sizes' => '100vw', 'priority' => true]) ?>
                    <div class="hero-overlay"></div>
                    <div class="hero-body">
                        <div class="container">
                            <span class="hero-eyebrow">
                                <svg class="i" aria-hidden="true"><use href="#i-monitor"/></svg>
                                Коворкинг · Wi-Fi · Компьютерные залы
                            </span>
                            <p class="hero-title">Технологии для вашего роста</p>
                            <p class="hero-text">Коворкинг-зоны, компьютерные залы и высокоскоростной Wi-Fi для продуктивной работы.</p>
                            <a class="btn btn-nlb-green btn-lg-nlb btn-icon" href="/services/">
                                Наши услуги
                                <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="carousel-item">
                    <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/hero-3.jpg', 'Лекционный зал', ['class' => 'hero-img', 'sizes' => '100vw', 'priority' => true]) ?>
                    <div class="hero-overlay"></div>
                    <div class="hero-body">
                        <div class="container">
                            <span class="hero-eyebrow">
                                <svg class="i" aria-hidden="true"><use href="#i-calendar"/></svg>
                                Лекции · Мастер-классы · Встречи
                            </span>
                            <p class="hero-title">События, которые вдохновляют</p>
                            <p class="hero-text">Лекции, мастер-классы и культурные мероприятия, которые проходят каждую неделю.</p>
                            <a class="btn btn-nlb-green btn-lg-nlb btn-icon" href="/events/">
                                Календарь событий
                                <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="hero-arrow"><svg class="i i-24" aria-hidden="true"><use href="#i-chevron-left"/></svg></span>
                <span class="visually-hidden">Предыдущий слайд</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="hero-arrow"><svg class="i i-24" aria-hidden="true"><use href="#i-chevron-right"/></svg></span>
                <span class="visually-hidden">Следующий слайд</span>
            </button>
        </div>
    </section>

    <section class="section" id="services">
        <div class="container">
            <div class="section-head" data-reveal>
                <span class="eyebrow eyebrow--red">Наши услуги</span>
                <h2 class="section-title">Комфорт и технологии для вашего обучения</h2>
                <p class="section-lead">Мы создали современное пространство, где каждый найдёт идеальные условия для работы, учёбы и творчества.</p>
            </div>

            <div class="row g-4">
                <div class="col-sm-6 col-lg-4" data-reveal>
                    <div class="service-card">
                        <span class="icon-tile icon-tile--blue"><svg class="i" aria-hidden="true"><use href="#i-wifi"/></svg></span>
                        <h3>Бесплатный Wi-Fi</h3>
                        <p>Высокоскоростной интернет во всех зонах</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4" data-reveal>
                    <div class="service-card">
                        <span class="icon-tile icon-tile--orange"><svg class="i" aria-hidden="true"><use href="#i-cup"/></svg></span>
                        <h3>Кафе и зоны отдыха</h3>
                        <p>Комфортные пространства для работы и перерывов</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4" data-reveal>
                    <div class="service-card">
                        <span class="icon-tile icon-tile--violet"><svg class="i" aria-hidden="true"><use href="#i-monitor"/></svg></span>
                        <h3>Компьютерные залы</h3>
                        <p>Современное оборудование и рабочие места</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4" data-reveal>
                    <div class="service-card">
                        <span class="icon-tile icon-tile--green"><svg class="i" aria-hidden="true"><use href="#i-users"/></svg></span>
                        <h3>Залы для мероприятий</h3>
                        <p>Конференц-залы, лектории и аудитории</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4" data-reveal>
                    <div class="service-card">
                        <span class="icon-tile icon-tile--sky"><svg class="i" aria-hidden="true"><use href="#i-printer"/></svg></span>
                        <h3>Печать и сканирование</h3>
                        <p>Копировальные услуги и полиграфия</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4" data-reveal>
                    <div class="service-card">
                        <span class="icon-tile"><svg class="i" aria-hidden="true"><use href="#i-clock"/></svg></span>
                        <h3>Гибкий график</h3>
                        <p>Работаем ежедневно с 10:00 до 22:00</p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5" data-reveal>
                <a class="btn btn-nlb-green btn-lg-nlb" href="/services/">Все услуги</a>
            </div>
        </div>
    </section>

    <section class="section section--soft" id="print">
        <div class="container">
            <div class="section-head" data-reveal>
                <span class="eyebrow eyebrow--violet">Полиграфия</span>
                <h2 class="section-title">Каталог печатных услуг</h2>
                <p class="section-lead">Печать, сканирование, переплёт и сувениры — от одного экземпляра до крупного тиража.</p>
            </div>

            <div class="scroller-wrap" data-scroller data-reveal>
                <div class="scroller" role="list">
                    <article class="print-card" role="listitem">
                        <div class="print-media">
                            <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/print-docs.jpg', 'Печать документов', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw']) ?>
                            <span class="print-badge bg-grad-primary"><svg class="i" aria-hidden="true"><use href="#i-printer"/></svg></span>
                        </div>
                        <div class="print-body">
                            <h3 class="print-title">Печать и копирование</h3>
                            <p class="print-text">Ч/б и цветная печать, копирование, печать с USB и e-mail.</p>
                            <div class="fw-bold text-dark mb-2">от 5 ₽</div>
                            <a class="print-link" href="/printing/">
                                Подробнее
                                <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                            </a>
                        </div>
                    </article>

                    <article class="print-card" role="listitem">
                        <div class="print-media">
                            <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/print-scanning.jpg', 'Сканирование документов', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw']) ?>
                            <span class="print-badge bg-grad-green"><svg class="i" aria-hidden="true"><use href="#i-scan"/></svg></span>
                        </div>
                        <div class="print-body">
                            <h3 class="print-title">Сканирование</h3>
                            <p class="print-text">Документы и фотографии, сохранение на USB и отправка на e-mail.</p>
                            <div class="fw-bold text-dark mb-2">от 10 ₽</div>
                            <a class="print-link" href="/printing/">
                                Подробнее
                                <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                            </a>
                        </div>
                    </article>

                    <article class="print-card" role="listitem">
                        <div class="print-media">
                            <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/print-cards.jpg', 'Печать визиток', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw']) ?>
                            <span class="print-badge bg-grad-sky"><svg class="i" aria-hidden="true"><use href="#i-card"/></svg></span>
                        </div>
                        <div class="print-body">
                            <h3 class="print-title">Визитки</h3>
                            <p class="print-text">Односторонние и двухсторонние, дизайнерская бумага, срочная печать.</p>
                            <div class="fw-bold text-dark mb-2">от 400 ₽</div>
                            <a class="print-link" href="/printing/">
                                Подробнее
                                <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                            </a>
                        </div>
                    </article>

                    <article class="print-card" role="listitem">
                        <div class="print-media">
                            <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/print-brochures.jpg', 'Брошюры и буклеты', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw']) ?>
                            <span class="print-badge bg-grad-pink"><svg class="i" aria-hidden="true"><use href="#i-file"/></svg></span>
                        </div>
                        <div class="print-body">
                            <h3 class="print-title">Брошюры и буклеты</h3>
                            <p class="print-text">Рекламные материалы, каталоги и фирменные буклеты.</p>
                            <div class="fw-bold text-dark mb-2">от 50 ₽</div>
                            <a class="print-link" href="/printing/">
                                Подробнее
                                <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                            </a>
                        </div>
                    </article>

                    <article class="print-card" role="listitem">
                        <div class="print-media">
                            <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/print-large.jpg', 'Широкоформатная печать', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw']) ?>
                            <span class="print-badge bg-grad-orange"><svg class="i" aria-hidden="true"><use href="#i-package"/></svg></span>
                        </div>
                        <div class="print-body">
                            <h3 class="print-title">Широкоформатная печать</h3>
                            <p class="print-text">Баннеры, плакаты и постеры любого размера.</p>
                            <div class="fw-bold text-dark mb-2">от 200 ₽</div>
                            <a class="print-link" href="/printing/">
                                Подробнее
                                <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                            </a>
                        </div>
                    </article>

                    <article class="print-card" role="listitem">
                        <div class="print-media">
                            <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/print-canvas.jpg', 'Печать на холсте', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw']) ?>
                            <span class="print-badge bg-grad-green"><svg class="i" aria-hidden="true"><use href="#i-image"/></svg></span>
                        </div>
                        <div class="print-body">
                            <h3 class="print-title">Печать на холсте</h3>
                            <p class="print-text">Фотографии и репродукции на натуральном холсте.</p>
                            <div class="fw-bold text-dark mb-2">от 900 ₽</div>
                            <a class="print-link" href="/printing/">
                                Подробнее
                                <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                            </a>
                        </div>
                    </article>

                    <article class="print-card" role="listitem">
                        <div class="print-media">
                            <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/print-mug.jpg', 'Печать на кружках', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw']) ?>
                            <span class="print-badge bg-grad-orange"><svg class="i" aria-hidden="true"><use href="#i-cup"/></svg></span>
                        </div>
                        <div class="print-body">
                            <h3 class="print-title">Печать на кружках</h3>
                            <p class="print-text">Кружки с вашим фото, логотипом или надписью.</p>
                            <div class="fw-bold text-dark mb-2">от 350 ₽</div>
                            <a class="print-link" href="/printing/">
                                Подробнее
                                <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                            </a>
                        </div>
                    </article>

                    <article class="print-card" role="listitem">
                        <div class="print-media">
                            <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/print-tshirts.jpg', 'Печать на футболках', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw']) ?>
                            <span class="print-badge bg-grad-sky"><svg class="i" aria-hidden="true"><use href="#i-package"/></svg></span>
                        </div>
                        <div class="print-body">
                            <h3 class="print-title">Печать на футболках</h3>
                            <p class="print-text">Футболки и свитшоты с вашим дизайном или логотипом.</p>
                            <div class="fw-bold text-dark mb-2">от 800 ₽</div>
                            <a class="print-link" href="/printing/">
                                Подробнее
                                <svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                            </a>
                        </div>
                    </article>
                </div>

                <button class="scroller-btn scroller-btn--prev" type="button" data-scroll-prev aria-label="Предыдущие услуги">
                    <svg class="i i-20" aria-hidden="true"><use href="#i-chevron-left"/></svg>
                </button>
                <button class="scroller-btn scroller-btn--next" type="button" data-scroll-next aria-label="Следующие услуги">
                    <svg class="i i-20" aria-hidden="true"><use href="#i-chevron-right"/></svg>
                </button>
            </div>

            <div class="text-center mt-4" data-reveal>
                <a class="btn btn-nlb-sky btn-lg-nlb" href="/printing/">Перейти в каталог</a>
            </div>
        </div>
    </section>

    <section class="section" id="events">
        <div class="container">
            <div class="section-head" data-reveal>
                <span class="eyebrow eyebrow--green">События</span>
                <h2 class="section-title">Предстоящие мероприятия</h2>
                <p class="section-lead">Присоединяйтесь к нашим лекциям, мастер-классам и встречам.</p>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-lg-4" data-reveal>
                    <article class="nlb-card">
                        <div class="nlb-card__media">
                            <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/event.jpg', 'Встреча с автором', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 33vw']) ?>
                            <span class="event-cat bg-grad-sky">Литература</span>
                            <span class="event-date"><b>15</b><span>апр</span></span>
                        </div>
                        <div class="nlb-card__body">
                            <h3 class="event-title">Встреча с автором</h3>
                            <p class="event-text">Лекция и автограф-сессия с современным писателем.</p>
                            <div class="event-meta">
                                <span><svg class="i i-20" aria-hidden="true"><use href="#i-clock"/></svg>18:00</span>
                                <span><svg class="i i-20" aria-hidden="true"><use href="#i-pin"/></svg>Конференц-зал</span>
                                <span><svg class="i i-20" aria-hidden="true"><use href="#i-users"/></svg>45 участников</span>
                            </div>
                            <a class="event-btn" href="/events/">Зарегистрироваться</a>
                        </div>
                    </article>
                </div>

                <div class="col-lg-4" data-reveal>
                    <article class="nlb-card">
                        <div class="nlb-card__media">
                            <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/event.jpg', 'Детский читательский клуб', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 33vw']) ?>
                            <span class="event-cat bg-grad-pink">Для детей</span>
                            <span class="event-date"><b>22</b><span>апр</span></span>
                        </div>
                        <div class="nlb-card__body">
                            <h3 class="event-title">Детский читательский клуб</h3>
                            <p class="event-text">Интерактивное чтение и творческие мастер-классы.</p>
                            <div class="event-meta">
                                <span><svg class="i i-20" aria-hidden="true"><use href="#i-clock"/></svg>15:00</span>
                                <span><svg class="i i-20" aria-hidden="true"><use href="#i-pin"/></svg>Детская зона</span>
                                <span><svg class="i i-20" aria-hidden="true"><use href="#i-users"/></svg>30 участников</span>
                            </div>
                            <a class="event-btn" href="/events/">Зарегистрироваться</a>
                        </div>
                    </article>
                </div>

                <div class="col-lg-4" data-reveal>
                    <article class="nlb-card">
                        <div class="nlb-card__media">
                            <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/event.jpg', 'Цифровые технологии в образовании', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 33vw']) ?>
                            <span class="event-cat bg-grad-green">Технологии</span>
                            <span class="event-date"><b>28</b><span>апр</span></span>
                        </div>
                        <div class="nlb-card__body">
                            <h3 class="event-title">Цифровые технологии в образовании</h3>
                            <p class="event-text">Практический семинар по использованию ИИ в обучении.</p>
                            <div class="event-meta">
                                <span><svg class="i i-20" aria-hidden="true"><use href="#i-clock"/></svg>19:00</span>
                                <span><svg class="i i-20" aria-hidden="true"><use href="#i-pin"/></svg>Медиа-зал</span>
                                <span><svg class="i i-20" aria-hidden="true"><use href="#i-users"/></svg>60 участников</span>
                            </div>
                            <a class="event-btn" href="/events/">Зарегистрироваться</a>
                        </div>
                    </article>
                </div>
            </div>

            <div class="cta-banner" data-reveal>
                <h2>Не пропустите наши события</h2>
                <p class="text-white-75 mb-4">Подпишитесь на рассылку и получайте уведомления о новых мероприятиях.</p>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                    <a class="btn btn-nlb-white btn-lg-nlb" href="/subscribe/">Подписаться на рассылку</a>
                    <a class="btn btn-nlb-outline btn-lg-nlb" href="/events/">Все мероприятия</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--soft" id="news">
        <div class="container">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4" data-reveal>
                <div>
                    <span class="eyebrow eyebrow--green">Блог</span>
                    <h2 class="section-title mb-0">Новости</h2>
                </div>
                <a class="btn btn-nlb-outline" href="/news/">Все новости</a>
            </div>

            <div class="row g-4">
                <div class="col-sm-6 col-lg-3" data-reveal>
                    <article class="nlb-card">
                        <a class="d-block h-100" href="/news/">
                            <div class="nlb-card__media">
                                <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/news-1.jpg', 'ИИ-ассистент в библиотеке', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 33vw']) ?>
                            </div>
                            <div class="nlb-card__body">
                                <p class="news-date">18 июня 2024</p>
                                <h3 class="news-title">Новая эра библиотеки: внедрение ИИ-ассистента</h3>
                                <p class="news-text clamp-2">Виртуальный помощник подскажет книги, ответит на вопросы и поможет с навигацией.</p>
                            </div>
                        </a>
                    </article>
                </div>

                <div class="col-sm-6 col-lg-3" data-reveal>
                    <article class="nlb-card">
                        <a class="d-block h-100" href="/news/">
                            <div class="nlb-card__media">
                                <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/news-2.jpg', 'Летний читальный зал', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 33vw']) ?>
                            </div>
                            <div class="nlb-card__body">
                                <p class="news-date">15 июня 2024</p>
                                <h3 class="news-title">Летний читальный зал открыт!</h3>
                                <p class="news-text clamp-2">Наслаждайтесь книгами на свежем воздухе в уютном летнем дворике.</p>
                            </div>
                        </a>
                    </article>
                </div>

                <div class="col-sm-6 col-lg-3" data-reveal>
                    <article class="nlb-card">
                        <a class="d-block h-100" href="/news/">
                            <div class="nlb-card__media">
                                <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/news-3.jpg', 'Новые книги', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 33vw']) ?>
                            </div>
                            <div class="nlb-card__body">
                                <p class="news-date">12 июня 2024</p>
                                <h3 class="news-title">Новые поступления: научная фантастика</h3>
                                <p class="news-text clamp-2">Более 50 новых книг в жанре научной фантастики уже на полках.</p>
                            </div>
                        </a>
                    </article>
                </div>

                <div class="col-sm-6 col-lg-3" data-reveal>
                    <article class="nlb-card">
                        <a class="d-block h-100" href="/news/">
                            <div class="nlb-card__media">
                                <?= nlb_picture(SITE_TEMPLATE_PATH . '/assets/images/news-4.jpg', 'Встреча с писателем', ['sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 33vw']) ?>
                            </div>
                            <div class="nlb-card__body">
                                <p class="news-date">10 июня 2024</p>
                                <h3 class="news-title">Онлайн-встреча с писателем-фантастом</h3>
                                <p class="news-text clamp-2">Обсуждение новой книги и творческих планов с известным автором.</p>
                            </div>
                        </a>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="collections">
        <div class="container">
            <div class="section-head section-head--left" data-reveal>
                <span class="eyebrow eyebrow--violet">Сувениры</span>
                <h2 class="section-title">Готовые подарки с символикой библиотеки</h2>
                <p class="section-lead">Шесть наборов на разные поводы: коллегам, детям, в дорогу и на память о Минске.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4" data-reveal>
                    <div class="collection-card collection-card--blue">
                        <span class="icon-tile icon-tile--blue"><svg class="i" aria-hidden="true"><use href="#i-package"/></svg></span>
                        <h3>Набор для коллег</h3>
                        <div class="collection-count">Предметов: 3</div>
                        <p class="collection-text">Брелок, ежедневник и открытка в крафтовом конверте.</p>
                        <a class="collection-more" href="/collections/nabor-dlya-kolleg/">Узнать больше <svg class="i i-20" aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4" data-reveal>
                    <div class="collection-card collection-card--green">
                        <span class="icon-tile icon-tile--green"><svg class="i" aria-hidden="true"><use href="#i-cup"/></svg></span>
                        <h3>Набор для дома</h3>
                        <div class="collection-count">Предметов: 2</div>
                        <p class="collection-text">Кружка с логотипом и хлопковый шоппер.</p>
                        <a class="collection-more" href="/collections/nabor-dlya-doma/">Узнать больше <svg class="i i-20" aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4" data-reveal>
                    <div class="collection-card collection-card--sky">
                        <span class="icon-tile icon-tile--sky"><svg class="i" aria-hidden="true"><use href="#i-book"/></svg></span>
                        <h3>Набор «Читаю»</h3>
                        <div class="collection-count">Предметов: 2</div>
                        <p class="collection-text">Футболка с надписью «Читаю» и шоппер для книг.</p>
                        <a class="collection-more" href="/collections/nabor-chitayu/">Узнать больше <svg class="i i-20" aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4" data-reveal>
                    <div class="collection-card collection-card--orange">
                        <span class="icon-tile icon-tile--orange"><svg class="i" aria-hidden="true"><use href="#i-globe"/></svg></span>
                        <h3>Набор для путешествий</h3>
                        <div class="collection-count">Предметов: 2</div>
                        <p class="collection-text">Термокружка и компактный зонт с принтом.</p>
                        <a class="collection-more" href="/collections/nabor-dlya-puteshestviy/">Узнать больше <svg class="i i-20" aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4" data-reveal>
                    <div class="collection-card collection-card--violet">
                        <span class="icon-tile icon-tile--violet"><svg class="i" aria-hidden="true"><use href="#i-users"/></svg></span>
                        <h3>Набор для детей</h3>
                        <div class="collection-count">Предметов: 3</div>
                        <p class="collection-text">Брелок «Сова», карандаши и магнит.</p>
                        <a class="collection-more" href="/collections/nabor-dlya-detey/">Узнать больше <svg class="i i-20" aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4" data-reveal>
                    <div class="collection-card collection-card--amber">
                        <span class="icon-tile"><svg class="i" aria-hidden="true"><use href="#i-image"/></svg></span>
                        <h3>Набор на память о Минске</h3>
                        <div class="collection-count">Предметов: 4</div>
                        <p class="collection-text">Открытки, магнит и брелок с видами города.</p>
                        <a class="collection-more" href="/collections/nabor-na-pamyat-o-minske/">Узнать больше <svg class="i i-20" aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5" data-reveal>
                <a class="btn btn-nlb btn-lg-nlb" href="/collections/">Все наборы</a>
            </div>
        </div>
    </section>

    <section class="partners">
        <div class="container">
            <div class="text-center mb-4" data-reveal>
                <h2 class="text-white h4 mb-1">Нам доверяют</h2>
                <p class="text-white-75 mb-0 small">Партнёры и культурные институции</p>
            </div>
            <div class="row g-3 justify-content-center">
                <div class="col-6 col-md-4 col-lg-2" data-reveal><div class="partner"><svg class="i" aria-hidden="true"><use href="#i-check"/></svg>Минкультуры</div></div>
                <div class="col-6 col-md-4 col-lg-2" data-reveal><div class="partner">Белкнига</div></div>
                <div class="col-6 col-md-4 col-lg-2" data-reveal><div class="partner">БГУ</div></div>
                <div class="col-6 col-md-4 col-lg-2" data-reveal><div class="partner">НАН Беларуси</div></div>
                <div class="col-6 col-md-4 col-lg-2" data-reveal><div class="partner">Минскгорисполком</div></div>
            </div>
        </div>
    </section>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
