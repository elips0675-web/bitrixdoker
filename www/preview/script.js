/*!
 * NLB Bootstrap — script.js
 * Только vanilla JS + Bootstrap 5 bundle. jQuery не требуется.
 */

(function () {
    'use strict';

    const doc = document;
    const root = doc.documentElement;

    /* ------------------------------------------------------------------
     * 1. Тень у «прилипшей» шапки
     * ------------------------------------------------------------------ */
    const header = doc.getElementById('siteHeader');
    const onScrollHeader = function () {
        if (!header) return;
        header.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    window.addEventListener('scroll', onScrollHeader, { passive: true });
    onScrollHeader();

    /* ------------------------------------------------------------------
     * 2. Появление блоков при прокрутке
     * ------------------------------------------------------------------ */
    const revealItems = doc.querySelectorAll('[data-reveal]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (revealItems.length && !reduceMotion && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

        revealItems.forEach(function (item, index) {
            item.classList.add('reveal');
            item.style.transitionDelay = (index % 4) * 60 + 'ms';
            observer.observe(item);
        });
    }

    /* ------------------------------------------------------------------
     * 3. Горизонтальная карусель «Печатные услуги»
     * ------------------------------------------------------------------ */
    doc.querySelectorAll('[data-scroller]').forEach(function (wrap) {
        const track = wrap.querySelector('.scroller');
        const prev = wrap.querySelector('[data-scroll-prev]');
        const next = wrap.querySelector('[data-scroll-next]');
        if (!track) return;

        const step = function () {
            const card = track.querySelector('.print-card');
            if (!card) return Math.round(track.clientWidth * 0.8);
            const gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || 0);
            return card.getBoundingClientRect().width + gap;
        };

        const updateButtons = function () {
            const max = track.scrollWidth - track.clientWidth - 2;
            if (prev) prev.disabled = track.scrollLeft <= 2;
            if (next) next.disabled = track.scrollLeft >= max;
        };

        if (prev) {
            prev.addEventListener('click', function () {
                track.scrollBy({ left: -step(), behavior: 'smooth' });
            });
        }

        if (next) {
            next.addEventListener('click', function () {
                track.scrollBy({ left: step(), behavior: 'smooth' });
            });
        }

        track.addEventListener('scroll', updateButtons, { passive: true });
        window.addEventListener('resize', updateButtons);
        updateButtons();
    });

    /* ------------------------------------------------------------------
     * 4. Плавающая кнопка связи (FAB)
     * ------------------------------------------------------------------ */
    const fab = doc.querySelector('.fab');
    if (fab) {
        const toggle = fab.querySelector('.fab-toggle');

        const setFab = function (open) {
            fab.classList.toggle('is-open', open);
            if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        if (toggle) {
            toggle.addEventListener('click', function (event) {
                event.stopPropagation();
                setFab(!fab.classList.contains('is-open'));
            });
        }

        // Пункт открывает мессенджер, почту или модальное окно —
        // после выбора меню не должно оставаться раскрытым
        fab.querySelectorAll('.fab-item').forEach(function (item) {
            item.addEventListener('click', function () {
                setFab(false);
            });
        });

        doc.addEventListener('click', function (event) {
            if (!fab.contains(event.target)) setFab(false);
        });

        doc.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') setFab(false);
        });
    }

    /* ------------------------------------------------------------------
     * 4.1. Чат ИИ-помощника
     *      Разметка — footer.php (.nlb-chat), стили — styles.css.
     *      Ответы — локальные правила; USE_REAL_AI переключает на бэкенд.
     * ------------------------------------------------------------------ */
    const chat = doc.getElementById('nlbChat');
    if (chat) {
        const chatLog = doc.getElementById('nlbChatLog');
        const chatForm = doc.getElementById('nlbChatForm');
        const chatInput = doc.getElementById('nlbChatInput');
        const chatStatus = doc.getElementById('nlbChatStatus');
        const chatBack = chat.querySelector('[data-chat-back]');
        const chatExpand = chat.querySelector('[data-chat-expand]');
        const chatError = doc.getElementById('nlbChatError');
        const chatOpeners = doc.querySelectorAll('[data-chat-open]');
        const chatClosers = chat.querySelectorAll('[data-chat-close]');
        const chatPrompt = doc.getElementById('nlbChatPrompt');

        const USE_REAL_AI = false;
        const API_URL = '/api/chat';

        let chatStarted = false;
        let chatTyping = null;
        let lastUserQuestion = '';
        let chatRun = 0;
        let chatMoreId = 0;
        let chatUserCount = 0;
        let chatPromoCount = 0;
        let chatPromoAt = 0;
        let chatPromoIndex = 0;
        let promoSeen = {};

        const RULES = [
            {
                re: /часы|часов|график|режим работ|время работ|работает|санитарн|во сколько|до скольк/,
                a: 'Библиотека открыта ежедневно с 10:00 до 22:00.\nСанитарный день — последний вторник месяца.\n\nУ пространств свой график: коворкинг — с 08:00, детская комната — до 21:00. Точное время — в разделе «Услуги».',
                links: [{ path: 'services/', label: 'Услуги и пространства' }],
                chips: [
                    { label: 'Адрес и как добраться' },
                    { label: 'Услуги и пространства' }
                ]
            },
            {
                re: /адрес|находит|добрат|доехать|метро|проезд|остановк|куда|где вы/,
                a: 'Мы в Минске на проспекте Независимости, 116.\n• Вход — со стороны проспекта.\n• От метро — 7–10 минут пешком.\n• На автобусе или троллейбусе — до остановки «Библиотека».\n• Для маломобильных посетителей есть пандус и лифт.\n\nИнтерактивной карты на сайте нет: маршрут уточняйте по телефону +375 (17) 300-00-00.',
                links: [{ path: 'contacts/', label: 'Контакты' }],
                chips: [
                    { label: 'Часы работы' },
                    { label: 'Контакты' }
                ]
            },
            {
                re: /услуг|пространств|коворкинг|сауна|кафе|спортзал|детск|обзорн|площадк|лектори|концертн/,
                a: 'В библиотеке восемь услуг:\n• Обзорная площадка — бесплатно для читателей\n• Коворкинг — от 200 ₽/час\n• Детская комната — от 250 ₽/час\n• Сауна — от 500 ₽/час\n• Спортзал — от 1000 ₽/месяц\n• Кафе — средний чек 300 ₽\n• Лекторий — аренда от 5000 ₽/час\n• Концертный зал — аренда от 10 000 ₽/час',
                more: 'Расписание и тарифы — в разделе «Услуги». Бронирование залов — по телефону +375 (17) 300-00-00 или через форму на странице услуги.',
                links: [{ path: 'services/', label: 'Услуги и пространства' }],
                chips: [
                    { label: 'Забронировать зал' },
                    { label: 'Печать и копирование' }
                ]
            },
            {
                re: /брон|аренд|снять|переговорн|забронир/,
                a: 'Залы и пространства можно забронировать:\n• Лекторий — от 5000 ₽/час\n• Концертный зал — от 10 000 ₽/час\n• Коворкинг — от 200 ₽/час\n• Детская комната — от 250 ₽/час\n• Сауна — от 500 ₽/час',
                more: 'Напишите дату, время и число гостей. Форма бронирования есть на страницах услуг, также можно позвонить: +375 (17) 300-00-00.',
                links: [{ path: 'services/', label: 'Услуги и пространства' }],
                chips: [
                    { label: 'Услуги и пространства' },
                    { label: 'Написать библиотекарю', action: askQuestion }
                ]
            },
            {
                re: /событи|мероприят|афиш|расписан|лекци|встреч|мастер-класс|экскурс|концерт|поэтич|игротек|клуб/,
                a: 'Ближайшие события:\n• 06.11, 18:30 — лекция «Как читать современную прозу», зал «Диалог»\n• 08.11, 11:00 — детский книжный клуб «Совёнок», детская комната\n• 12.11, 19:00 — мастер-класс «Нейросети для гуманитариев», компьютерный класс\n• 15.11, 17:00 — поэтический вечер «Осенние строки»\n• 21.11, 15:00 — экскурсия по фонду редких книг, по записи\n• 29.11, 12:00 — игротека настольных игр',
                more: 'Вход бесплатный. Запись на события — по телефону +375 (17) 300-00-00. Полная афиша — в разделе «События».',
                links: [{ path: 'events/', label: 'Афиша событий' }],
                chips: [
                    { label: 'Услуги и пространства' },
                    { label: 'Задать свой вопрос', action: focusQuestion }
                ]
            },
            {
                re: /печат|копир|сканир|принтер|распечат|визитк|буклет|перепл|ламинир|ксерокс/,
                a: 'Печать и копирование — от 5 ₽ за страницу, сканирование — от 10 ₽, переплёт и ламинирование — от 250 ₽, визитки — от 350 ₽ за 50 штук.',
                more: 'Всего 37 печатных услуг: документы, визитки, брошюры, широкий формат, фото и сувениры с логотипом. Файлы принимаем на флешке, по почте или через форму на сайте. Полный каталог — в разделе «Печать».',
                links: [{ path: 'printing/', label: 'Печать' }],
                chips: [
                    { label: 'Услуги и пространства' },
                    { label: 'Контакты' }
                ]
            },
            {
                re: /запис|читательск|абонемент|билет|стать читател/,
                a: 'Читательский билет оформляется бесплатно в библиотеке.',
                more: 'Регистрация на сайте не заменяет билет: она нужна для личного кабинета и заявок. Основные пространства доступны и без записи. Подробнее — в разделе «Услуги» или на стойке информации.',
                links: [{ path: 'services/', label: 'Услуги и пространства' }],
                chips: [
                    { label: 'Услуги и пространства' },
                    { label: 'Часы работы' }
                ]
            },
            {
                re: /книг|каталог|поиск|найти|автор|фонд|подборк|литератур|нет в наличии/,
                a: 'Искать книги удобнее через поиск по сайту: раздел «Поиск» ищет по книгам, событиям, новостям, услугам и печатному каталогу.',
                more: 'Если нужной книги нет в каталоге — спросите на стойке информации: подскажем, где её найти, или оформим заказ по межбиблиотечному обмену. Подборки книг — в разделе «Подборки».',
                links: [
                    { path: 'books/', label: 'Книги' },
                    { path: 'collections/', label: 'Подборки' }
                ],
                chips: [
                    { label: 'Электронные ресурсы и Wi-Fi' },
                    { label: 'Контакты' }
                ]
            },
            {
                re: /wi-?fi|вай-?фай|интернет|электронн|компьютер|цифров|базы данных|онлайн-ресурс/,
                a: 'Wi-Fi в библиотеке бесплатный, есть компьютеры и цифровые ресурсы.\n\nДоступ к электронным ресурсам — через читательский билет: запись в библиотеку, продление книг и удалённые базы. Подробности — на стойке информации и по телефону +375 (17) 300-00-00.',
                links: [{ path: 'services/', label: 'Услуги и пространства' }],
                chips: [
                    { label: 'Как записаться в библиотеку' },
                    { label: 'Контакты' }
                ]
            },
            {
                re: /телефон|контакт|почт|e-?mail|позвон|справочн|написать|связаться/,
                a: 'Телефон справочной и записи на события: +375 (17) 300-00-00.\nE-mail: info@example.by.\n\nАдрес: Минск, пр. Независимости, 116. Работаем ежедневно с 10:00 до 22:00.\n\nВсе контакты — в разделе «Контакты».',
                links: [{ path: 'contacts/', label: 'Контакты' }],
                chips: [
                    { label: 'Часы работы' },
                    { label: 'Адрес и как добраться' }
                ]
            },
            {
                re: /оператор|человек|менеджер|живой|позов|позват|соедин|жалоб|не понял/,
                a: 'Живого оператора в этом демо нет: отвечает виртуальный помощник по правилам сайта.\n\nЕсли нужен человек — оставьте вопрос в форме обратной связи, библиотекарь ответит в течение рабочего дня. Или позвоните: +375 (17) 300-00-00.',
                links: [{ path: 'contacts/', label: 'Контакты' }],
                chips: [
                    { label: 'Отправить вопрос библиотекарю', accent: true, action: askQuestion },
                    { label: 'Часы работы' }
                ],
                promo: false
            }
        ];

        const PROMOS = [
            {
                re: /спортзал|фитнес|трениров|стройн|похудет|качал/,
                text: 'Кстати, если хотите быть в форме — у нас спортзал от 1000 ₽/месяц. Дешевле, чем фитнес-клуб, и без очереди к беговой дорожке 💪',
                path: 'services/gym/',
                label: 'Спортзал'
            },
            {
                re: /саун|парн|баня|отдохн|расслаб/,
                text: 'А если день выдался тяжёлым — сауна от 500 ₽/час. Пар, тишина и книга: три составляющие хорошего вечера 🧖',
                path: 'services/sauna/',
                label: 'Сауна'
            },
            {
                re: /коворкинг|работ|ноутбук|офис|тишин/,
                text: 'Ищете тихий угол для работы? Коворкинг от 200 ₽/час: Wi-Fi есть, коллег с чипсами нет 🎧',
                path: 'services/coworking/',
                label: 'Коворкинг'
            },
            {
                re: /дет|ребен|ребён|школьн|малыш/,
                text: 'Если вы с детьми — детская комната от 250 ₽/час. Взрослые читают, дети играют, никто никому не мешает 🧸',
                path: 'services/kids-zone/',
                label: 'Детская комната'
            },
            {
                re: /кафе|кофе|обед|перекус|еда/,
                text: 'Кстати, в кафе средний чек 300 ₽. Кофе с книгой — и день уже удался ☕',
                path: 'services/cafe/',
                label: 'Кафе'
            },
            {
                re: /лектори|лекци|доклад|презентац/,
                text: 'А лекторий можно арендовать от 5000 ₽/час — если ваша лекция должна собрать всех, а не только соседа 🎤',
                path: 'services/lecture-hall/',
                label: 'Лекторий'
            },
            {
                re: /концерт|музык|групп|выступ/,
                text: 'Концертный зал — от 10 000 ₽/час. Для музыки, которая уже не помещается в наушники 🎸',
                path: 'services/concert-hall/',
                label: 'Концертный зал'
            },
            {
                re: /обзорн|площадк|панорам/,
                text: 'Совет: обзорная площадка бесплатна для читателей. Лучший вид на Минск и ноль рублей за билет 🌇',
                path: 'services/observation-deck/',
                label: 'Обзорная площадка'
            },
            {
                re: /печат|визитк|копир|скан/,
                text: 'Нужны визитки? 350 ₽ за 50 штук — дешевле, чем каждый раз объяснять, кто вы, на словах 😄',
                path: 'printing/',
                label: 'Печать'
            },
            {
                re: /книг|подборк|чтен|почитать|каталог/,
                text: 'Не знаете, что читать? Загляните в «Подборки»: там книги, уже проверенные другими читателями 📚',
                path: 'collections/',
                label: 'Подборки'
            }
        ];

        const THANKS = /спасибо|благодар|понятно|окей/;

        const GREETING = /привет|здравств|добрый|хай|hello|hi\b/;

        const chatTime = function () {
            return new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        };

        const chatNearBottom = function () {
            return chatLog.scrollHeight - chatLog.scrollTop - chatLog.clientHeight < 48;
        };

        const scrollChat = function (options) {
            const opts = options || {};
            if (!opts.force && !opts.stick) return;

            const row = opts.row;
            if (row && row.offsetHeight > chatLog.clientHeight) {
                const logRect = chatLog.getBoundingClientRect();
                const rowRect = row.getBoundingClientRect();
                const target = chatLog.scrollTop + (rowRect.top - logRect.top) - 8;
                chatLog.scrollTop = Math.max(0, target);
                return;
            }

            chatLog.scrollTop = chatLog.scrollHeight;
        };

        const chatHrefCache = {};

        const chatHref = function (path) {
            if (Object.prototype.hasOwnProperty.call(chatHrefCache, path)) return chatHrefCache[path];

            let href = path;
            const links = doc.querySelectorAll('a[href]');
            for (let i = 0; i < links.length; i++) {
                const value = links[i].getAttribute('href') || '';
                if (value.length > path.length && value.slice(-path.length) === path) {
                    href = value;
                    break;
                }
            }

            chatHrefCache[path] = href;
            return href;
        };

        const chatSaveRating = function (answer, value) {
            try {
                const raw = window.localStorage.getItem('nlbChatRating');
                const parsed = raw ? JSON.parse(raw) : [];
                const items = Array.isArray(parsed) ? parsed : [];
                items.push({
                    q: lastUserQuestion,
                    a: String(answer).slice(0, 120),
                    v: value,
                    t: Date.now()
                });
                window.localStorage.setItem('nlbChatRating', JSON.stringify(items.slice(-50)));
            } catch (error) {
                return;
            }
        };

        const chatRate = function (text) {
            const wrap = doc.createElement('div');
            wrap.className = 'nlb-chat__rate';
            wrap.setAttribute('role', 'group');
            wrap.setAttribute('aria-label', 'Оценить ответ');

            [
                { value: 1, icon: '#i-thumb-up', label: 'Ответ полезен', thanks: 'Спасибо, учту' },
                { value: -1, icon: '#i-thumb-down', label: 'Ответ не полезен', thanks: 'Спасибо, попробую иначе' }
            ].forEach(function (item) {
                const button = doc.createElement('button');
                button.type = 'button';
                button.className = 'nlb-chat__rate-btn';
                button.setAttribute('aria-label', item.label);
                button.setAttribute('aria-pressed', 'false');
                button.innerHTML = '<svg class="i" aria-hidden="true"><use href="' + item.icon + '"/></svg>';
                button.addEventListener('click', function () {
                    if (wrap.classList.contains('is-rated')) return;
                    wrap.classList.add('is-rated');
                    wrap.querySelectorAll('.nlb-chat__rate-btn').forEach(function (node) {
                        node.disabled = true;
                        node.classList.toggle('is-active', node === button);
                        node.setAttribute('aria-pressed', node === button ? 'true' : 'false');
                    });
                    const thanks = doc.createElement('span');
                    thanks.className = 'nlb-chat__rate-thanks';
                    thanks.textContent = item.thanks;
                    wrap.appendChild(thanks);
                    chatSaveRating(text, item.value);
                });
                wrap.appendChild(button);
            });

            return wrap;
        };

        const addChatMessage = function (text, who, opts) {
            const options = opts || {};
            const stick = chatNearBottom();
            const row = doc.createElement('div');
            row.className = 'nlb-chat__msg nlb-chat__msg--' + who;

            const bubble = doc.createElement('div');
            bubble.className = 'nlb-chat__bubble';
            bubble.appendChild(doc.createTextNode(text));

            if (options.more) {
                const details = doc.createElement('div');
                details.className = 'nlb-chat__more';
                details.id = 'nlbChatMore' + (++chatMoreId);
                details.hidden = true;
                details.textContent = options.more;
                bubble.appendChild(details);

                const toggleWrap = doc.createElement('div');
                toggleWrap.className = 'nlb-chat__more-toggle';

                const toggle = doc.createElement('button');
                toggle.type = 'button';
                toggle.className = 'nlb-chat__more-btn';
                toggle.textContent = 'Подробнее';
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-controls', details.id);
                toggle.addEventListener('click', function () {
                    const open = details.hidden;
                    details.hidden = !open;
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    toggle.textContent = open ? 'Свернуть' : 'Подробнее';
                });

                toggleWrap.appendChild(toggle);
                bubble.appendChild(toggleWrap);
            }

            row.appendChild(bubble);

            if (options.links && options.links.length) {
                const src = doc.createElement('div');
                src.className = 'nlb-chat__src';
                src.appendChild(doc.createTextNode('Источник: '));
                options.links.forEach(function (item, index) {
                    if (index > 0) src.appendChild(doc.createTextNode(', '));
                    const link = doc.createElement('a');
                    link.className = 'nlb-chat__src-link';
                    link.href = chatHref(item.path);
                    link.textContent = item.label;
                    src.appendChild(link);
                });
                row.appendChild(src);
            }

            if (options.promo && who === 'bot') {
                const promo = doc.createElement('div');
                promo.className = 'nlb-chat__promo';

                const promoText = doc.createElement('span');
                promoText.className = 'nlb-chat__promo-text';
                promoText.textContent = options.promo.text;
                promo.appendChild(promoText);

                const promoLink = doc.createElement('a');
                promoLink.className = 'nlb-chat__promo-link';
                promoLink.href = chatHref(options.promo.path);
                promoLink.textContent = options.promo.label;
                promo.appendChild(promoLink);

                row.appendChild(promo);
            }

            if (options.rate && who === 'bot') {
                row.appendChild(chatRate(text));
            }

            const time = doc.createElement('div');
            time.className = 'nlb-chat__time';
            time.textContent = chatTime();
            row.appendChild(time);

            chatLog.appendChild(row);
            scrollChat({ row: row, force: who === 'user', stick: stick });
            return row;
        };

        const hideChatTyping = function () {
            if (chatTyping) {
                chatTyping.remove();
                chatTyping = null;
            }
        };

        const showChatTyping = function () {
            hideChatTyping();
            const stick = chatNearBottom();
            const row = doc.createElement('div');
            row.className = 'nlb-chat__msg nlb-chat__msg--bot nlb-chat__typing';
            row.innerHTML = '<div class="nlb-chat__bubble"><span></span><span></span><span></span></div>';
            chatLog.appendChild(row);
            chatTyping = row;
            scrollChat({ stick: stick });
        };

        const chatLater = function (fn, delay) {
            const run = chatRun;
            window.setTimeout(function () {
                if (run === chatRun) fn();
            }, delay);
        };

        const chatReply = function (text, delay, done, links, rate, more, promo) {
            showChatTyping();
            chatLater(function () {
                hideChatTyping();
                addChatMessage(text, 'bot', { links: links, rate: rate, more: more, promo: promo });
                if (done) done();
            }, delay);
        };

        const addChatChips = function (items) {
            const stick = chatNearBottom();
            const wrap = doc.createElement('div');
            wrap.className = 'nlb-chat__chips';
            items.forEach(function (item) {
                const chip = doc.createElement('button');
                chip.type = 'button';
                chip.className = 'nlb-chat__chip' + (item.accent ? ' nlb-chat__chip--accent' : '');
                chip.textContent = item.label;
                chip.addEventListener('click', function () {
                    wrap.remove();
                    if (item.action) item.action();
                    else sendChat(item.label);
                });
                wrap.appendChild(chip);
            });
            chatLog.appendChild(wrap);
            scrollChat({ stick: stick });
        };

        const clearChatChips = function () {
            chatLog.querySelectorAll('.nlb-chat__chips').forEach(function (node) {
                node.remove();
            });
        };

        const askBackend = function (text) {
            if (!USE_REAL_AI) return Promise.resolve(null);
            return fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_id: 'nlb_chat', message: text, history: [] })
            }).then(function (response) {
                return response.ok ? response.json() : null;
            }).then(function (data) {
                return data && data.reply ? String(data.reply) : null;
            }).catch(function () {
                return null;
            });
        };

        const startChips = function () {
            return [
                { label: 'Часы работы' },
                { label: 'Адрес и как добраться' },
                { label: 'Услуги и пространства' },
                { label: 'Афиша событий' },
                { label: 'Задать свой вопрос', accent: true, action: focusQuestion },
                { label: 'Написать библиотекарю', action: askQuestion }
            ];
        };

        const pageSection = function () {
            const path = String(window.location.pathname || '/').toLowerCase();
            const sections = ['services', 'printing', 'events', 'news', 'books', 'collections', 'contacts', 'feedback'];
            for (let i = 0; i < sections.length; i++) {
                if (path.indexOf('/' + sections[i] + '/') !== -1) return sections[i];
            }
            return 'home';
        };

        const sectionGreeting = function () {
            const section = pageSection();

            if (section === 'services') {
                return {
                    text: 'Здравствуйте! Я Татьяна Петровна, ИИ-помощник библиотеки. Вижу, вы смотрите услуги и пространства, — подскажу тарифы, график и бронирование. Чем помочь?',
                    chips: [
                        { label: 'Услуги и пространства' },
                        { label: 'Часы работы' },
                        { label: 'Забронировать зал' },
                        { label: 'Задать свой вопрос', accent: true, action: focusQuestion },
                        { label: 'Написать библиотекарю', action: askQuestion }
                    ]
                };
            }

            if (section === 'printing') {
                return {
                    text: 'Здравствуйте! Я Татьяна Петровна, ИИ-помощник библиотеки. Вижу, вы в разделе «Печать», — подскажу цены, форматы и как передать файл. Чем помочь?',
                    chips: [
                        { label: 'Печать и копирование' },
                        { label: 'Визитки' },
                        { label: 'Часы работы' },
                        { label: 'Задать свой вопрос', accent: true, action: focusQuestion },
                        { label: 'Написать библиотекарю', action: askQuestion }
                    ]
                };
            }

            if (section === 'events') {
                return {
                    text: 'Здравствуйте! Я Татьяна Петровна, ИИ-помощник библиотеки. Вижу, вы смотрите афишу, — подскажу ближайшие события и как на них записаться. Чем помочь?',
                    chips: [
                        { label: 'Ближайшие события' },
                        { label: 'Как записаться на событие' },
                        { label: 'Часы работы' },
                        { label: 'Задать свой вопрос', accent: true, action: focusQuestion },
                        { label: 'Написать библиотекарю', action: askQuestion }
                    ]
                };
            }

            if (section === 'news') {
                return {
                    text: 'Здравствуйте! Я Татьяна Петровна, ИИ-помощник библиотеки. Вижу, вы читаете новости, — подскажу, что нового в библиотеке и куда прийти. Чем помочь?',
                    chips: [
                        { label: 'Афиша событий' },
                        { label: 'Услуги и пространства' },
                        { label: 'Часы работы' },
                        { label: 'Задать свой вопрос', accent: true, action: focusQuestion }
                    ]
                };
            }

            if (section === 'books' || section === 'collections') {
                return {
                    text: 'Здравствуйте! Я Татьяна Петровна, ИИ-помощник библиотеки. Вижу, вы выбираете книги, — подскажу, как искать по каталогу и где выдают. Чем помочь?',
                    chips: [
                        { label: 'Поиск по каталогу' },
                        { label: 'Как записаться в библиотеку' },
                        { label: 'Электронные ресурсы и Wi-Fi' },
                        { label: 'Задать свой вопрос', accent: true, action: focusQuestion }
                    ]
                };
            }

            if (section === 'contacts') {
                return {
                    text: 'Здравствуйте! Я Татьяна Петровна, ИИ-помощник библиотеки. Вижу, вы на странице контактов, — подскажу телефон, адрес и часы работы. Чем помочь?',
                    chips: [
                        { label: 'Часы работы' },
                        { label: 'Адрес и как добраться' },
                        { label: 'Написать библиотекарю', action: askQuestion }
                    ]
                };
            }

            if (section === 'feedback') {
                return {
                    text: 'Здравствуйте! Я Татьяна Петровна, ИИ-помощник библиотеки. Помогу сформулировать вопрос или передам его библиотекарю. Чем помочь?',
                    chips: [
                        { label: 'Задать свой вопрос', accent: true, action: focusQuestion },
                        { label: 'Написать библиотекарю', action: askQuestion },
                        { label: 'Часы работы' }
                    ]
                };
            }

            return {
                text: 'Здравствуйте! Меня зовут Татьяна Петровна, я ИИ-помощник ТП НББ. Чем могу помочь?',
                chips: startChips()
            };
        };

        const pickPromo = function (value) {
            if (chatPromoCount >= 3) return null;

            const gap = chatPromoCount === 0 ? 2 : 3;
            if (chatUserCount - chatPromoAt < gap) return null;

            for (let n = 0; n < PROMOS.length; n++) {
                const promo = PROMOS[(chatPromoIndex + n) % PROMOS.length];
                if (promoSeen[promo.label]) continue;
                if (promo.re.test(value)) continue;
                chatPromoIndex = (chatPromoIndex + n + 1) % PROMOS.length;
                return promo;
            }

            return null;
        };

        const markPromo = function (promo) {
            promoSeen[promo.label] = true;
            chatPromoCount++;
            chatPromoAt = chatUserCount;
        };

        const answerLocally = function (value) {
            if (GREETING.test(value)) {
                chatReply('Здравствуйте! Я Татьяна Петровна, ИИ-помощник библиотеки. Подскажу часы работы, как записаться, услуги и пространства, печать, бронирование, афишу, книги, Wi-Fi и контакты. Чем помочь?', 500, function () {
                    addChatChips(startChips());
                });
                return;
            }

            if (THANKS.test(value)) {
                chatReply('Всегда рад помочь 😊 Если появятся вопросы — пишите.', 400, function () {
                    addChatChips([
                        { label: 'Задать свой вопрос', accent: true, action: focusQuestion },
                        { label: 'Написать библиотекарю', action: askQuestion }
                    ]);
                });
                return;
            }

            const rule = RULES.find(function (item) {
                return item.re.test(value);
            });

            if (rule) {
                const promo = rule.promo === false ? null : pickPromo(value);
                if (promo) markPromo(promo);
                chatReply(rule.a, 700, function () {
                    if (rule.chips) addChatChips(rule.chips);
                }, rule.links, true, rule.more, promo);
                return;
            }

            chatReply('Не нашёл ответа на этот вопрос 🤔 Я подсказываю про часы и адрес, запись, услуги, печать, бронирование, афишу, книги, электронные ресурсы и контакты.\n\nМожно отправить вопрос библиотекарю — он ответит в течение рабочего дня.', 900, function () {
                addChatChips([
                    { label: 'Отправить вопрос библиотекарю', accent: true, action: askQuestion },
                    { label: 'Написать библиотекарю', action: askQuestion },
                    { label: 'Задать другой вопрос', action: focusQuestion }
                ]);
            }, [{ path: 'contacts/', label: 'Контакты' }], true);
        };

        function focusQuestion() {
            chatReply('Напишите вопрос — постараюсь ответить. Если не найду ответ, предложу передать его библиотекарю.', 400, function () {
                chatInput.focus();
                chatInput.placeholder = 'Ваш вопрос…';
            });
        }

        function askQuestion() {
            const text = String(lastUserQuestion || '').trim();
            const modal = doc.getElementById('feedbackModal');

            closeChat();

            if (!modal) {
                window.location.href = 'feedback/';
                return;
            }

            const message = modal.querySelector('textarea[name="MESSAGE"]');
            if (message && text && (!message.value.trim() || message.dataset.nlbChat === '1')) {
                message.value = text;
                message.dataset.nlbChat = '1';
            }

            const subject = modal.querySelector('input[name="SUBJECT"]');
            if (subject && !subject.value.trim()) {
                subject.value = 'Вопрос из чата';
            }

            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modal).show();
                window.setTimeout(function () {
                    if (message) message.focus();
                }, 400);
                return;
            }

            const form = modal.querySelector('form');
            window.location.href = form ? form.getAttribute('action') : 'feedback/';
        }

        const chatTranscript = function () {
            const lines = [];
            chatLog.querySelectorAll('.nlb-chat__msg').forEach(function (node) {
                if (node.classList.contains('nlb-chat__typing')) return;
                const bubble = node.querySelector('.nlb-chat__bubble');
                if (!bubble) return;
                const who = node.classList.contains('nlb-chat__msg--user') ? 'Вы' : 'ИИ-помощник';
                lines.push(who + ': ' + bubble.textContent);
            });
            return lines.join('\n\n');
        };

        const chatCopyStatus = function (ok) {
            const message = ok ? 'диалог скопирован' : 'не удалось скопировать';
            const previous = chatStatus.textContent;
            chatStatus.textContent = message;
            window.setTimeout(function () {
                if (chatStatus.textContent === message) chatStatus.textContent = previous;
            }, 1800);
        };

        function copyChat() {
            const text = chatTranscript();
            if (!text) {
                chatCopyStatus(false);
                return;
            }

            const fallback = function () {
                const area = doc.createElement('textarea');
                area.value = text;
                area.setAttribute('readonly', 'readonly');
                area.style.position = 'fixed';
                area.style.top = '-1000px';
                doc.body.appendChild(area);
                area.select();

                let ok = false;
                try {
                    ok = doc.execCommand('copy');
                } catch (error) {
                    ok = false;
                }

                area.remove();
                chatCopyStatus(ok);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    chatCopyStatus(true);
                }).catch(fallback);
                return;
            }

            fallback();
        }

        const chatMic = doc.getElementById('nlbChatMic');
        const ChatRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        let chatRecognition = null;

        const showMicError = function (message) {
            if (!chatError) return;
            chatError.textContent = message;
            chatError.hidden = false;
        };

        const setChatMicState = function (listening) {
            if (!chatMic) return;
            chatMic.classList.toggle('is-listening', listening);
            chatMic.setAttribute('aria-pressed', listening ? 'true' : 'false');
            chatMic.setAttribute('aria-label', listening ? 'Остановить голосовой ввод' : 'Голосовой ввод');
        };

        const stopChatMic = function () {
            if (!chatRecognition) return;
            try {
                chatRecognition.stop();
            } catch (error) {
                setChatMicState(false);
            }
            setChatMicState(false);
        };

        if (chatMic && ChatRecognition) {
            chatMic.hidden = false;
            chatRecognition = new ChatRecognition();
            chatRecognition.lang = 'ru-RU';
            chatRecognition.interimResults = false;
            chatRecognition.maxAlternatives = 1;
            chatRecognition.continuous = false;

            chatRecognition.addEventListener('start', function () {
                setChatMicState(true);
            });

            chatRecognition.addEventListener('result', function (event) {
                const result = event.results && event.results[0] && event.results[0][0];
                const text = result ? String(result.transcript || '').trim() : '';
                if (!text) return;
                const current = chatInput.value.trim();
                chatInput.value = current ? current + ' ' + text : text;
                chatInput.dispatchEvent(new Event('input', { bubbles: true }));
                chatInput.focus();
            });

            chatRecognition.addEventListener('end', function () {
                setChatMicState(false);
            });

            chatRecognition.addEventListener('error', function (event) {
                setChatMicState(false);
                if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                    showMicError('Нет доступа к микрофону: разрешите его в настройках браузера или наберите вопрос текстом.');
                } else if (event.error === 'no-speech') {
                    showMicError('Не расслышал — попробуйте ещё раз или наберите вопрос текстом.');
                } else {
                    showMicError('Голосовой ввод не сработал — наберите вопрос текстом.');
                }
            });

            chatMic.addEventListener('click', function () {
                if (chatMic.classList.contains('is-listening')) {
                    stopChatMic();
                    return;
                }
                hideChatError();
                try {
                    chatRecognition.start();
                } catch (error) {
                    setChatMicState(false);
                    showMicError('Голосовой ввод сейчас недоступен — наберите вопрос текстом.');
                }
            });
        }

        function resetChat() {
            stopChatMic();
            chatRun++;
            lastUserQuestion = '';
            chatUserCount = 0;
            chatPromoCount = 0;
            chatPromoAt = 0;
            chatPromoIndex = 0;
            promoSeen = {};
            hideChatTyping();
            hideChatError();
            clearChatChips();
            chatLog.innerHTML = '';
            chatLog.scrollTop = 0;
            chatInput.value = '';
            chatInput.placeholder = 'Напишите сообщение…';
            greetChat();
            if (chat.classList.contains('is-open')) chatInput.focus();
        }

        /* nlb-chat-filter:start */
        const CHAT_BLOCKED = [
            'хуй', 'хуи', 'хуе', 'хуя', 'хую', 'пизд', 'ебат', 'ебан', 'ебал', 'ебл', 'ебуч', 'бля',
            'сука', 'суки', 'суке', 'суку', 'сукой', 'сучар', 'залуп', 'уеб', 'пидор', 'гандон', 'шлюх', 'мраз', 'дебил',
            'кретин', 'идиот', 'дурак', 'дура', 'чмо', 'урод', 'говн', 'дерьм',
            'жоп', 'сран', 'срак', 'жидок', 'жиды', 'жидов',
            'мудак', 'мудил', 'придур', 'сдохн', 'заткн', 'туп', 'долбое',
            'пошел ты', 'пошла ты',
            'fuck', 'shit', 'bitch', 'cunt', 'asshole', 'dick', 'bastard', 'slut',
            'whore', 'nigger', 'idiot', 'stupid', 'moron', 'loser', 'shut up', 'hate you'
        ];

        const CHAT_NEUTRAL = /дурацк[а-я]*|дурачеств[а-я]*|идиотск[а-я]*/g;

        const CHAT_SENSITIVE = [
            'убий', 'убить', 'убил', 'убью', 'убива', 'насил', 'террор', 'экстрем',
            'взорв', 'взрыв', 'расправ', 'оружи', 'нарко', 'порно', 'секс',
            'проститут', 'ненавиж', 'kill', 'murder', 'terror', 'violen', 'weapon', 'drug', 'porn'
        ];

        const CHAT_SPAM = [
            'казино', 'бинарн', 'пирамид', 'опцион', 'мошенн', 'спам',
            'криптовалют', 'casino', 'crypto', 'pyramid'
        ];

        const CHAT_STUDY = /книг|литератур|роман|повест|учебник|пособ|истор|биограф|энциклопед|автор|издани|почита|найти|фонд|каталог|реферат|курсов|диплом|исследован|стать/;
        const CHAT_LINK = /(https?:\/\/|www\.|t\.me\/|vk\.com\/)/;
        const CHAT_MASH = [
            'asdf', 'sdfg', 'dfgh', 'fghj', 'ghjk', 'hjkl', 'zxcv', 'xcvb',
            'йцук', 'цуке', 'укен', 'фыва', 'ывап', 'вапр', 'апро', 'олдж', 'ячсм', 'чсми'
        ];

        const chatHit = function (value, roots) {
            for (let i = 0; i < roots.length; i++) {
                const root = roots[i];
                if (new RegExp('(^|[^a-zа-я])' + root).test(value)) return true;
            }
            return false;
        };

        const chatGibberish = function (value) {
            if (!value) return false;
            if (/(.)\1{4,}/.test(value)) return true;

            const letters = value.replace(/[^a-zа-я]/g, '');
            if (letters.length >= 6) {
                const unique = {};
                let count = 0;
                for (let i = 0; i < letters.length; i++) {
                    const letter = letters.charAt(i);
                    if (!unique[letter]) {
                        unique[letter] = true;
                        count++;
                    }
                }
                if (count / letters.length < 0.35) return true;

                const triples = {};
                for (let i = 0; i <= letters.length - 3; i++) {
                    const triple = letters.slice(i, i + 3);
                    triples[triple] = (triples[triple] || 0) + 1;
                    if (triples[triple] >= 3) return true;
                }
            }

            const words = value.split(/\s+/);
            for (let i = 0; i < words.length; i++) {
                const word = words[i].replace(/[^a-zа-я]/g, '');
                if (word.length < 3) continue;
                if (!/[aeiouyаеиоуыэюя]/.test(word)) return true;
                if (/[bcdfghjklmnpqrstvwxzбвгджзйклмнпрстфхцчшщ]{6,}/.test(word)) return true;
                for (let j = 0; j < CHAT_MASH.length; j++) {
                    if (word.indexOf(CHAT_MASH[j]) !== -1) return true;
                }
            }

            return false;
        };

        const chatProblem = function (text) {
            const value = String(text || '').toLowerCase().replace(/ё/g, 'е').replace(CHAT_NEUTRAL, ' ');
            const study = CHAT_STUDY.test(value);

            if (chatHit(value, CHAT_BLOCKED) || chatHit(value, CHAT_SPAM)) return 'rude';
            if (!study && chatHit(value, CHAT_SENSITIVE)) return 'rude';

            const links = value
                .split('www.nlb.by').join('').split('www.example.by').join('')
                .split('nlb.by').join('').split('example.by').join('');
            if (CHAT_LINK.test(links)) return 'spam';

            if (chatGibberish(links)) return 'gibberish';
            return '';
        };
        /* nlb-chat-filter:end */

        const CHAT_ERRORS = {
            rude: 'Давайте без грубостей, рекламы и подозрительных предложений. Напишите вопрос спокойно — помогу с книгами, залами, печатью и афишей.',
            spam: 'Ссылки в чате не принимаются. Напишите вопрос словами или оставьте его в форме обратной связи.',
            gibberish: 'Не разобрал сообщение. Напишите вопрос словами — например: «часы работы» или «как записаться».'
        };

        const showChatError = function (kind) {
            if (!chatError) return;
            chatError.textContent = CHAT_ERRORS[kind] || CHAT_ERRORS.gibberish;
            chatError.hidden = false;
            chatInput.setAttribute('aria-invalid', 'true');
            chatInput.classList.add('is-invalid');
            if (chat.classList.contains('is-open')) chatInput.focus();
        };

        const hideChatError = function () {
            if (!chatError) return;
            chatError.hidden = true;
            chatInput.removeAttribute('aria-invalid');
            chatInput.classList.remove('is-invalid');
        };

        const handleChat = function (text) {
            const value = text.toLowerCase().replace(/ё/g, 'е');

            askBackend(text).then(function (answer) {
                if (answer) {
                    chatReply(answer, 800);
                    return;
                }
                answerLocally(value);
            });
        };

        function sendChat(text) {
            const value = String(text || '').trim();
            if (!value) return;

            const problem = chatProblem(value);
            if (problem) {
                showChatError(problem);
                return;
            }

            hideChatError();
            clearChatChips();
            lastUserQuestion = value;
            chatUserCount++;
            addChatMessage(value, 'user');
            chatInput.value = '';
            handleChat(value);
        }

        const greetChat = function () {
            const greeting = sectionGreeting();
            const row = addChatMessage(greeting.text, 'bot');
            row.classList.add('nlb-chat__msg--greeting');
            addChatChips(greeting.chips);
            chatLog.scrollTop = 0;
        };

        const CHAT_PROMPT_KEY = 'nlbChatPrompt';

        const hideChatPrompt = function () {
            if (!chatPrompt || chatPrompt.hidden) return;
            chatPrompt.hidden = true;
        };

        const showChatPrompt = function () {
            if (!chatPrompt || !chatPrompt.hidden || chatStarted) return;

            let seen = false;
            try {
                seen = window.sessionStorage.getItem(CHAT_PROMPT_KEY) === '1';
            } catch (error) {
                seen = false;
            }
            if (seen) return;

            chatPrompt.hidden = false;
            try {
                window.sessionStorage.setItem(CHAT_PROMPT_KEY, '1');
            } catch (error) {
                return;
            }
        };

        const openChat = function () {
            hideChatPrompt();
            chat.classList.add('is-open');
            chat.setAttribute('aria-hidden', 'false');
            chatOpeners.forEach(function (opener) {
                opener.setAttribute('aria-expanded', 'true');
            });
            if (!chatStarted) {
                chatStarted = true;
                greetChat();
            }
            window.setTimeout(function () {
                chatInput.focus();
            }, 220);
        };

        const setChatExpanded = function (expanded) {
            chat.classList.toggle('is-expanded', expanded);
            if (!chatExpand) return;
            chatExpand.setAttribute('aria-pressed', expanded ? 'true' : 'false');
            chatExpand.setAttribute('aria-label', expanded ? 'Свернуть чат' : 'Развернуть чат');
            const icon = chatExpand.querySelector('use');
            if (icon) icon.setAttribute('href', expanded ? '#i-collapse' : '#i-expand');
        };

        const closeChat = function () {
            stopChatMic();
            setChatExpanded(false);
            chat.classList.remove('is-open');
            chat.setAttribute('aria-hidden', 'true');
            chatOpeners.forEach(function (opener) {
                opener.setAttribute('aria-expanded', 'false');
            });
        };

        chatOpeners.forEach(function (opener) {
            opener.addEventListener('click', openChat);
        });

        if (chatPrompt) {
            chatPrompt.querySelectorAll('[data-chat-prompt-close]').forEach(function (button) {
                button.addEventListener('click', hideChatPrompt);
            });
            window.setTimeout(showChatPrompt, 8000);
        }

        chatClosers.forEach(function (closer) {
            closer.addEventListener('click', closeChat);
        });

        chat.querySelectorAll('[data-chat-question]').forEach(function (button) {
            button.addEventListener('click', askQuestion);
        });

        chat.querySelectorAll('[data-chat-copy]').forEach(function (button) {
            button.addEventListener('click', copyChat);
        });

        if (chatBack) {
            chatBack.addEventListener('click', function () {
                resetChat();
            });
        }

        if (chatExpand) {
            chatExpand.addEventListener('click', function () {
                setChatExpanded(!chat.classList.contains('is-expanded'));
            });
        }

        chatForm.addEventListener('submit', function (event) {
            event.preventDefault();
            sendChat(chatInput.value);
        });

        chatInput.addEventListener('input', hideChatError);

        doc.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && chat.classList.contains('is-open')) closeChat();
        });
    }

    /* ------------------------------------------------------------------
     * 5. Кнопка «Наверх»
     * ------------------------------------------------------------------ */
    const toTop = doc.querySelector('.to-top');
    if (toTop) {
        const onScrollTop = function () {
            toTop.classList.toggle('is-visible', window.scrollY > 480);
        };
        window.addEventListener('scroll', onScrollTop, { passive: true });
        onScrollTop();

        toTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
        });
    }

    /* ------------------------------------------------------------------
     * 6. Мобильное меню: закрываем после клика по ссылке.
     *    Пункт с выпадающим списком не закрываем: иначе на телефоне
     *    нажатие «Услуги»/«Печать» сворачивало всё меню вместо того,
     *    чтобы показать подпункты.
     * ------------------------------------------------------------------ */
    const nav = doc.getElementById('nlbNav');
    if (nav && window.bootstrap) {
        nav.querySelectorAll('a.nav-link:not(.dropdown-toggle)').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth >= 992) return;
                window.bootstrap.Collapse.getOrCreateInstance(nav).hide();
            });
        });
    }

    /* ------------------------------------------------------------------
     * 7. Мгновенный поиск и автофокус в модальном окне
     *    Список под полем — прогрессивное улучшение: без него форма
     *    отправляется в /search/ как обычно, поэтому отказ эндпоинта
     *    (503 index_unavailable) ничего не ломает.
     * ------------------------------------------------------------------ */
    const searchModal = doc.getElementById('searchModal');
    if (searchModal) {
        searchModal.addEventListener('shown.bs.modal', function () {
            const input = searchModal.querySelector('input[type="search"]');
            if (input) input.focus();
        });

        const searchForm = searchModal.querySelector('form[role="search"]');
        const searchField = searchModal.querySelector('input[type="search"]');
        const searchInstant = searchModal.querySelector('#nlbSearchInstant');

        if (searchForm && searchField && searchInstant) {
            const searchEndpoint = String(
                searchForm.getAttribute('data-search-endpoint') || '/local/ajax/search.php'
            );
            let searchTimer = 0;
            let searchAbort = null;

            const clearInstant = function () {
                searchInstant.textContent = '';
                searchInstant.hidden = true;
            };

            const renderInstant = function (items) {
                searchInstant.textContent = '';
                if (!items.length) {
                    clearInstant();
                    return;
                }

                items.forEach(function (item) {
                    const link = doc.createElement('a');
                    link.className = 'search-instant__item';
                    link.href = String(item.url || '#');

                    const title = doc.createElement('span');
                    title.className = 'search-instant__title';
                    title.textContent = String(item.title || '');
                    link.appendChild(title);

                    if (item.snippet) {
                        const snippet = doc.createElement('span');
                        snippet.className = 'search-instant__snippet';
                        snippet.textContent = String(item.snippet);
                        link.appendChild(snippet);
                    }

                    searchInstant.appendChild(link);
                });

                searchInstant.hidden = false;
            };

            const runInstant = function () {
                const value = String(searchField.value || '').trim();
                if (value.length < 2) {
                    clearInstant();
                    return;
                }

                if (searchAbort) searchAbort.abort();
                const controller = typeof AbortController === 'function' ? new AbortController() : null;
                searchAbort = controller;

                const url = searchEndpoint + '?q=' + encodeURIComponent(value) + '&limit=5';
                const options = { headers: { Accept: 'application/json' } };
                if (controller) options.signal = controller.signal;

                fetch(url, options)
                    .then(function (response) {
                        return response.ok ? response.json() : null;
                    })
                    .then(function (data) {
                        if (!data || !data.ok) {
                            clearInstant();
                            return;
                        }
                        renderInstant(Array.isArray(data.results) ? data.results : []);
                    })
                    .catch(function () {
                        if (controller && controller.signal.aborted) return;
                        clearInstant();
                    });
            };

            searchField.addEventListener('input', function () {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(runInstant, 250);
            });

            searchField.addEventListener('search', clearInstant);

            searchModal.addEventListener('hidden.bs.modal', function () {
                window.clearTimeout(searchTimer);
                if (searchAbort) searchAbort.abort();
                clearInstant();
            });
        }
    }

    /* ------------------------------------------------------------------
     * 8. Bootstrap-валидация форм
     * ------------------------------------------------------------------ */
    doc.querySelectorAll('form.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    /* ------------------------------------------------------------------
     * 8.1. Формы заявок: тариф, свободные слоты, имя элемента
     *      Все данные уходят отдельными свойствами инфоблока, поэтому
     *      никакой «сборки сообщения» больше нет.
     * ------------------------------------------------------------------ */
    const setFormField = function (form, code, value) {
        form.querySelectorAll('[data-nlb-field="' + code + '"]').forEach(function (input) {
            input.value = value;
        });
    };

    const bookingName = function (form) {
        const value = function (code) {
            const input = form.querySelector('[data-nlb-field="' + code + '"]');
            return input ? String(input.value || '').trim() : '';
        };
        const service = value('SERVICE');
        const date = value('DATE');
        const time = value('TIME');
        let name = service !== '' ? 'Бронь: ' + service : 'Заявка на услугу';
        if (date !== '') name += ' — ' + date;
        if (time !== '') name += ' ' + time;
        setFormField(form, 'NAME', name);
    };

    doc.querySelectorAll('.js-choose-tariff').forEach(function (link) {
        link.addEventListener('click', function () {
            const card = link.closest('.tariff-card');
            if (card) {
                doc.querySelectorAll('.tariff-card.is-selected').forEach(function (one) {
                    one.classList.remove('is-selected');
                });
                card.classList.add('is-selected');
            }

            const form = doc.querySelector('form[data-nlb-form="booking"]');
            if (!form) return;
            setFormField(form, 'TARIFF', link.getAttribute('data-tariff') || '');
            setFormField(form, 'PRICE', link.getAttribute('data-price') || '');
            bookingName(form);
        });
    });

    doc.querySelectorAll('form[data-nlb-form="booking"]').forEach(function (form) {
        const date = form.querySelector('[data-nlb-field="DATE"]');
        const time = form.querySelector('[data-nlb-field="TIME"]');
        const hint = form.querySelector('[data-nlb-slots-hint]');
        let slots = {};
        try {
            slots = JSON.parse(form.getAttribute('data-nlb-slots') || '{}') || {};
        } catch (error) {
            slots = {};
        }

        const syncSlots = function () {
            if (!date || !time) return;
            const taken = Array.isArray(slots[date.value]) ? slots[date.value] : [];
            let available = 0;

            Array.from(time.options).forEach(function (option) {
                if (option.value === '') return;
                const busy = taken.indexOf(option.value) !== -1;
                option.disabled = busy;
                if (!busy) available++;
                if (busy && option.selected) time.value = '';
            });

            if (hint) {
                if (date.value === '') {
                    hint.textContent = '';
                } else if (available === 0) {
                    hint.textContent = 'На эту дату свободных слотов нет. Выберите другой день.';
                } else {
                    hint.textContent = 'Свободных слотов: ' + available + '.';
                }
            }
        };

        if (date) {
            date.addEventListener('change', function () {
                syncSlots();
                bookingName(form);
            });
        }
        if (time) {
            time.addEventListener('change', function () {
                bookingName(form);
            });
        }
        form.addEventListener('submit', function () {
            bookingName(form);
        });

        syncSlots();
        bookingName(form);
    });

    /* ------------------------------------------------------------------
     * 9. Подсказка «Ctrl + K» открывает поиск (как в исходном макете)
     * ------------------------------------------------------------------ */
    doc.addEventListener('keydown', function (event) {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            const trigger = doc.querySelector('[data-bs-target="#searchModal"]');
            if (!trigger || !window.bootstrap || !searchModal) return;
            event.preventDefault();
            window.bootstrap.Modal.getOrCreateInstance(searchModal).show();
        }
    });

    /* ------------------------------------------------------------------
     * 10. Нижние уведомления: отступ для плавающих кнопок
     *     Высота стека уведомлений уходит в --nlb-notice-h, чтобы кнопка
     *     «наверх» и меню связи не перекрывали баннеры cookie и PWA.
     * ------------------------------------------------------------------ */
    const noticeStack = doc.getElementById('nlbNotices');

    window.nlbSyncNotices = function () {
        if (!noticeStack) return;
        const height = noticeStack.offsetHeight;
        root.style.setProperty('--nlb-notice-h', height > 0 ? height + 'px' : '0px');
    };

    if (noticeStack) {
        window.nlbSyncNotices();
        window.addEventListener('resize', window.nlbSyncNotices, { passive: true });

        if (typeof window.ResizeObserver === 'function') {
            new window.ResizeObserver(window.nlbSyncNotices).observe(noticeStack);
        }
    }

    // Помечаем, что JS загрузился: CSS может опираться на html.js
    root.classList.add('js');
})();
