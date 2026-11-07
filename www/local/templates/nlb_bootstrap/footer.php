</main>

<?php
/**
 * NLB Bootstrap — footer.php
 *
 * @var CMain $APPLICATION
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$nlbSiteDir = SITE_DIR;
$nlbTpl = SITE_TEMPLATE_PATH;
?>

<footer class="site-footer" id="contacts">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-4">
                <a class="brand" href="<?= $nlbSiteDir ?>">
                    <span class="brand-mark">
                        <svg class="i" aria-hidden="true"><use href="#i-book"/></svg>
                    </span>
                    <span>
                        <span class="brand-name text-white">Библиотека 2026</span>
                        <span class="brand-tag">Центр знаний</span>
                    </span>
                </a>
                <p class="footer-about">
                    Современная библиотека, объединяющая традиции и инновации:
                    книги, цифровые ресурсы, события и пространства для работы.
                </p>
                <div class="footer-social" aria-label="Мы в социальных сетях">
                    <a href="https://t.me/example" target="_blank" rel="noopener noreferrer" aria-label="Telegram">
                        <svg class="i" aria-hidden="true"><use href="#i-send"/></svg>
                    </a>
                    <a href="https://vk.com/example" target="_blank" rel="noopener noreferrer" aria-label="ВКонтакте">
                        <span class="soc-vk">VK</span>
                    </a>
                    <a href="https://instagram.com/example" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                        <svg class="i" aria-hidden="true"><use href="#i-instagram"/></svg>
                    </a>
                    <a href="https://youtube.com/@example" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
                        <svg class="i" aria-hidden="true"><use href="#i-youtube"/></svg>
                    </a>
                </div>
            </div>

            <div class="col-6 col-lg-2">
                <h4>Читателям</h4>
                <ul class="footer-links">
                    <li><a href="<?= $nlbSiteDir ?>collections/">Подарочные наборы</a></li>
                    <li><a href="<?= $nlbSiteDir ?>events/">События</a></li>
                    <li><a href="<?= $nlbSiteDir ?>services/">Услуги</a></li>
                    <li><a href="<?= $nlbSiteDir ?>books/">Сувениры</a></li>
                    <li><a href="<?= $nlbSiteDir ?>rules/">Правила</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h4>Услуги</h4>
                <ul class="footer-links footer-links--green">
                    <li><a href="<?= $nlbSiteDir ?>services/">Читальные залы</a></li>
                    <li><a href="<?= $nlbSiteDir ?>services/">Электронная библиотека</a></li>
                    <li><a href="<?= $nlbSiteDir ?>printing/">Печать и копирование</a></li>
                    <li><a href="<?= $nlbSiteDir ?>services/">Экскурсии</a></li>
                    <li><a href="<?= $nlbSiteDir ?>services/">Обучение</a></li>
                </ul>
            </div>

            <div class="col-lg-4">
                <h4>Контакты</h4>
                <div class="footer-contact">
                    <svg class="i" aria-hidden="true"><use href="#i-pin"/></svg>
                    <span>220114, Минск,<br>пр. Независимости, 116</span>
                </div>
                <div class="footer-contact">
                    <svg class="i" aria-hidden="true"><use href="#i-clock"/></svg>
                    <span>Ежедневно 10:00–22:00</span>
                </div>
                <div class="footer-contact">
                    <svg class="i" aria-hidden="true"><use href="#i-phone"/></svg>
                    <a href="tel:+375173000000">+375 (17) 300-00-00</a>
                </div>
                <div class="footer-contact">
                    <svg class="i" aria-hidden="true"><use href="#i-mail"/></svg>
                    <a href="mailto:info@example.by">info@example.by</a>
                </div>

                <form class="newsletter mt-4 needs-validation" action="<?= $nlbSiteDir ?>subscribe/" method="post" novalidate>
                    <label class="visually-hidden" for="nlbSubscribeEmail">E-mail для подписки</label>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <input class="form-control" id="nlbSubscribeEmail" type="email" name="EMAIL"
                               placeholder="Ваш e-mail" autocomplete="email" required>
                        <button class="btn btn-nlb" type="submit">Подписаться</button>
                        <div class="invalid-feedback">Укажите корректный e-mail.</div>
                    </div>
                    <p class="form-hint mt-2 mb-0">Новости и анонсы. Без спама, отписка в один клик.</p>
                </form>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="mb-0">© <?= date('Y') ?> Библиотека 2026. Все права защищены.</p>
            <div class="d-flex flex-wrap gap-3">
                <a href="<?= $nlbSiteDir ?>privacy/">Политика конфиденциальности</a>
                <a href="<?= $nlbSiteDir ?>terms/">Условия использования</a>
                <a href="<?= $nlbSiteDir ?>sitemap/">Карта сайта</a>
                <button class="footer-link-button" type="button" data-cookie-settings>Cookie</button>
            </div>
        </div>
    </div>
    <div class="footer-gradient"></div>
</footer>

<div class="site-notices" id="nlbNotices">
    <div class="site-notice" id="nlbCookieNotice" role="status" hidden>
        <div class="container">
            <div class="site-notice__inner">
                <span class="site-notice__icon" aria-hidden="true">
                    <svg class="i i-24"><use href="#i-cookie"/></svg>
                </span>
                <div class="site-notice__text">
                    <p class="site-notice__title">Cookie и локальное хранилище</p>
                    <p class="site-notice__body mb-0">
                        Сайт использует только технические cookie: сессию, вход в кабинет
                        и защиту форм. Аналитики и рекламных трекеров нет.
                        <a href="<?= $nlbSiteDir ?>privacy/">Подробнее</a>
                    </p>
                </div>
                <div class="site-notice__actions">
                    <button class="btn btn-nlb btn-sm" type="button" data-cookie-choice="accepted">Принять</button>
                    <button class="btn btn-nlb-outline btn-sm" type="button" data-cookie-choice="required">
                        Только необходимые
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="site-notice site-notice--install" id="nlbInstallBanner" data-variant="install" hidden>
        <div class="container">
            <div class="site-notice__inner">
                <img class="site-notice__app" src="<?= $nlbTpl ?>/assets/icons/icon-192.png"
                     width="44" height="44" alt="" loading="lazy" decoding="async">
                <div class="site-notice__text">
                    <p class="site-notice__title">Установить приложение</p>
                    <p class="site-notice__body mb-0" data-pwa-text="install">
                        Библиотека под рукой: каталог, афиша и печать открываются
                        с домашнего экрана, без адресной строки.
                    </p>
                    <p class="site-notice__body mb-0" data-pwa-text="ios" hidden>
                        Нажмите «Поделиться» в Safari и выберите «На экран “Домой”».
                    </p>
                </div>
                <div class="site-notice__actions">
                    <button class="btn btn-nlb btn-sm" type="button" data-pwa-install>Установить</button>
                    <button class="btn btn-link btn-sm site-notice__later" type="button" data-notice-close>
                        Позже
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="fab" id="nlbFab">
    <div class="fab-menu" aria-label="Быстрая связь">
        <a class="fab-item" href="https://t.me/example" target="_blank" rel="noopener noreferrer">
            <span>Telegram</span>
            <span class="fab-ico fab-ico--tg"><svg class="i" aria-hidden="true"><use href="#i-send"/></svg></span>
        </a>
        <a class="fab-item" href="https://wa.me/375291234567" target="_blank" rel="noopener noreferrer">
            <span>WhatsApp</span>
            <span class="fab-ico fab-ico--wa"><svg class="i" aria-hidden="true"><use href="#i-whatsapp"/></svg></span>
        </a>
        <a class="fab-item" href="tel:+375173000000">
            <span>Позвонить</span>
            <span class="fab-ico fab-ico--phone"><svg class="i" aria-hidden="true"><use href="#i-phone"/></svg></span>
        </a>
        <a class="fab-item" href="mailto:info@example.by">
            <span>Написать на e-mail</span>
            <span class="fab-ico fab-ico--mail"><svg class="i" aria-hidden="true"><use href="#i-mail"/></svg></span>
        </a>
        <button class="fab-item border-0 bg-white" type="button" data-bs-toggle="modal" data-bs-target="#feedbackModal">
            <span>Обратная связь</span>
            <span class="fab-ico fab-ico--chat"><svg class="i" aria-hidden="true"><use href="#i-chat"/></svg></span>
        </button>
        <button class="fab-item border-0 bg-white" type="button" data-chat-open aria-controls="nlbChat" aria-expanded="false">
            <span>ИИ-помощник</span>
            <span class="fab-ico fab-ico--bot"><svg class="i" aria-hidden="true"><use href="#i-sparkles"/></svg></span>
        </button>
    </div>
    <button class="fab-toggle" type="button" aria-label="Открыть меню связи" aria-expanded="false" aria-controls="nlbFab">
        <svg class="i" aria-hidden="true"><use href="#i-plus"/></svg>
    </button>
</div>

<div class="nlb-chat__prompt" id="nlbChatPrompt" hidden>
    <button class="nlb-chat__prompt-main" type="button" data-chat-open aria-controls="nlbChat" aria-expanded="false">
        Нужна помощь? Спросите ИИ-помощника
    </button>
    <button class="nlb-chat__prompt-close" type="button" aria-label="Скрыть подсказку" data-chat-prompt-close></button>
</div>

<div class="nlb-chat" id="nlbChat" role="dialog" aria-labelledby="nlbChatTitle" aria-hidden="true">
    <div class="nlb-chat__head">
        <button class="nlb-chat__back" type="button" data-chat-back aria-label="Вернуться в начало диалога">
            <svg class="i" aria-hidden="true"><use href="#i-chevron-left"/></svg>
            <span class="nlb-chat__back-text">Назад</span>
        </button>
        <span class="nlb-chat__titles">
            <span class="nlb-chat__title" id="nlbChatTitle">Татьяна Петровна</span>
            <span class="nlb-chat__status" id="nlbChatStatus">ИИ-помощник ТП НББ · онлайн</span>
        </span>
        <span class="nlb-chat__avatar" aria-hidden="true">
            <img class="nlb-chat__avatar-img" src="<?= $nlbTpl ?>/assets/images/avatar-tatyana.svg" width="48" height="48" alt="" decoding="async">
        </span>
        <button class="nlb-chat__expand" type="button" aria-label="Развернуть чат" aria-pressed="false" data-chat-expand>
            <svg class="i" aria-hidden="true"><use href="#i-expand"/></svg>
        </button>
        <button class="btn-close btn-close-white nlb-chat__close" type="button" aria-label="Закрыть чат" data-chat-close></button>
    </div>
    <div class="nlb-chat__log" id="nlbChatLog" role="log" aria-live="polite" aria-relevant="additions"></div>
    <form class="nlb-chat__form" id="nlbChatForm" autocomplete="off">
        <label class="visually-hidden" for="nlbChatInput">Сообщение</label>
        <input class="nlb-chat__input" id="nlbChatInput" type="text" maxlength="500" placeholder="Напишите сообщение…" aria-describedby="nlbChatError">
        <button class="nlb-chat__mic" id="nlbChatMic" type="button" aria-label="Голосовой ввод" aria-pressed="false" hidden>
            <svg class="i" aria-hidden="true"><use href="#i-mic"/></svg>
        </button>
        <button class="nlb-chat__send" type="submit" aria-label="Отправить">
            <svg class="i" aria-hidden="true"><use href="#i-send"/></svg>
        </button>
    </form>
    <p class="nlb-chat__error" id="nlbChatError" role="alert" hidden></p>
    <p class="nlb-chat__hint">
        Не нашли ответ?
        <button class="nlb-chat__link" type="button" data-chat-question>Отправить вопрос библиотекарю</button>
        <span class="nlb-chat__hint-sep" aria-hidden="true">·</span>
        <button class="nlb-chat__link" type="button" data-chat-copy>Скопировать диалог</button>
    </p>
</div>

<button class="to-top" type="button" aria-label="Наверх">
    <svg class="i i-24" aria-hidden="true"><use href="#i-up"/></svg>
</button>

<div class="modal fade modal-nlb" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="feedbackModalLabel">Обратная связь</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <?php if (function_exists('nlb_form_render')): ?>
                    <?php
                    nlb_form_render('request', [
                        'action' => $nlbSiteDir . 'feedback/',
                        'layout' => 'modal',
                        'captcha' => true,
                        'form_id' => 'nlbModalRequest',
                        'values' => [
                            'PAGE' => (string)($_SERVER['REQUEST_URI'] ?? ''),
                        ],
                        'show_hint' => false,
                    ]);
                    ?>
                    <p class="form-hint mt-3 mb-0">
                        Заявка сохраняется в базе сайта. Можно также открыть
                        <a href="<?= $nlbSiteDir ?>feedback/">страницу обратной связи</a>.
                    </p>
                <?php else: ?>
                    <p class="mb-0">Форма доступна на <a href="<?= $nlbSiteDir ?>feedback/">странице обратной связи</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade modal-nlb" id="requisitesModal" tabindex="-1" aria-labelledby="requisitesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="requisitesModalLabel">Наши реквизиты</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Наименование</dt>
                    <dd class="col-sm-7">ГУ «Библиотека 2026»</dd>
                    <dt class="col-sm-5">УНП</dt>
                    <dd class="col-sm-7">000000000</dd>
                    <dt class="col-sm-5">Юридический адрес</dt>
                    <dd class="col-sm-7">220114, Минск, пр. Независимости, 116</dd>
                    <dt class="col-sm-5">Банк</dt>
                    <dd class="col-sm-7">ОАО «Пример-Банк»</dd>
                    <dt class="col-sm-5">Расчётный счёт</dt>
                    <dd class="col-sm-7 mb-0">BY00 XXXX 0000 0000 0000 0000 0000</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<script src="<?= $nlbTpl ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= $nlbTpl ?>/script.js"></script>
<script>window.NLB_PWA = { sw: '<?= $nlbSiteDir ?>sw.js', scope: '<?= $nlbSiteDir ?>' };</script>
<script src="<?= $nlbTpl ?>/assets/js/pwa.js"></script>
<script>
window.NLB_PUSH = {
    enabled: <?= function_exists('nlb_push_enabled') && nlb_push_enabled() ? 'true' : 'false' ?>,
    publicKey: '<?= htmlspecialcharsbx(function_exists('nlb_push_public_key') ? nlb_push_public_key() : '') ?>',
    endpoint: '<?= $nlbSiteDir ?>local/ajax/push.php',
    sessid: '<?= htmlspecialcharsbx(function_exists('bitrix_sessid') ? bitrix_sessid() : '') ?>'
};
</script>
<script src="<?= $nlbTpl ?>/assets/js/push.js"></script>
<script src="<?= $nlbTpl ?>/assets/js/consent.js"></script>
</body>
</html>
