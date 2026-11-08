<?php
/**
 * NLB Bootstrap — структура данных сайта библиотеки.
 *
 * Файл не требует ядра Битрикса: это чистый массив, который читают
 * миграции (db/migrations/*) и офлайн-линтер (db/lint.php).
 *
 * Соглашения:
 *  - ID типов инфоблоков — [a-z0-9_], префикс nlb_ (свои данные не смешиваем
 *    с демо-типами Битрикса);
 *  - коды инфоблоков — [a-z0-9_] без префикса: news, events, books...;
 *  - коды свойств — ВЕРХНИЙ_РЕГИСТР, как в шаблонах компонентов
 *    ($arItem['PROPERTIES']['AUTHOR']);
 *  - XML_ID элементов и разделов — nlb-<iblock>-<номер>, nlb-sec-<iblock>-<код>.
 *
 * Типы свойств: S — строка, N — число, L — список, F — файл.
 * user_type: 'DateTime' — свойство «дата/время» (хранится в формате сайта).
 */

return [
    /* ------------------------------------------------------------------ */
    /* 1. Типы инфоблоков                                                 */
    /* ------------------------------------------------------------------ */

    'types' => [
        'nlb_content' => [
            'name' => 'Контент библиотеки',
            'sort' => 100,
            'sections' => 'Y',
            'in_rss' => 'N',
            'lang' => [
                'ru' => [
                    'name' => 'Контент библиотеки',
                    'section_name' => 'Разделы',
                    'element_name' => 'Материалы',
                ],
                'en' => [
                    'name' => 'Library content',
                    'section_name' => 'Sections',
                    'element_name' => 'Items',
                ],
            ],
        ],
        'nlb_catalog' => [
            'name' => 'Каталог книг',
            'sort' => 200,
            'sections' => 'Y',
            'in_rss' => 'N',
            'lang' => [
                'ru' => [
                    'name' => 'Каталог книг',
                    'section_name' => 'Жанры',
                    'element_name' => 'Книги',
                ],
                'en' => [
                    'name' => 'Book catalog',
                    'section_name' => 'Genres',
                    'element_name' => 'Books',
                ],
            ],
        ],
        'nlb_services' => [
            'name' => 'Услуги библиотеки',
            'sort' => 300,
            'sections' => 'N',
            'in_rss' => 'N',
            'lang' => [
                'ru' => [
                    'name' => 'Услуги библиотеки',
                    'section_name' => 'Разделы',
                    'element_name' => 'Услуги',
                ],
                'en' => [
                    'name' => 'Library services',
                    'section_name' => 'Sections',
                    'element_name' => 'Services',
                ],
            ],
        ],
    ],

    /* ------------------------------------------------------------------ */
    /* 2. Инфоблоки                                                       */
    /* ------------------------------------------------------------------ */

    'iblocks' => [
        'news' => [
            'type' => 'nlb_content',
            'name' => 'Новости',
            'sort' => 100,
            'list_page_url' => '/news/',
            'detail_page_url' => '/news/#ELEMENT_CODE#/',
            'section_page_url' => '/news/?section=#SECTION_CODE#',
            'sections' => 'Y',
            'properties' => [
                'AUTHOR' => [
                    'name' => 'Автор',
                    'type' => 'S',
                    'sort' => 100,
                    'searchable' => 'Y',
                ],
                'TAGS' => [
                    'name' => 'Теги',
                    'type' => 'L',
                    'multiple' => 'Y',
                    'sort' => 110,
                    'values' => [
                        ['xml_id' => 'books', 'value' => 'Книги', 'sort' => 10],
                        ['xml_id' => 'digital', 'value' => 'Цифровые ресурсы', 'sort' => 20],
                        ['xml_id' => 'events', 'value' => 'События', 'sort' => 30],
                        ['xml_id' => 'kids', 'value' => 'Дети', 'sort' => 40],
                        ['xml_id' => 'science', 'value' => 'Наука', 'sort' => 50],
                        ['xml_id' => 'library', 'value' => 'Библиотека', 'sort' => 60],
                    ],
                ],
                'READ_TIME' => [
                    'name' => 'Время чтения',
                    'type' => 'S',
                    'sort' => 120,
                ],
                'SOURCE_URL' => [
                    'name' => 'Источник, ссылка',
                    'type' => 'S',
                    'sort' => 130,
                ],
            ],
            'sections_data' => [
                ['code' => 'library', 'name' => 'Новости библиотеки', 'sort' => 100],
                ['code' => 'announcements', 'name' => 'Анонсы', 'sort' => 110],
            ],
        ],

        'events' => [
            'type' => 'nlb_content',
            'name' => 'События',
            'sort' => 110,
            'list_page_url' => '/events/',
            'detail_page_url' => '/events/#ELEMENT_CODE#/',
            'section_page_url' => '/events/?section=#SECTION_CODE#',
            'sections' => 'Y',
            'properties' => [
                'DATE' => [
                    'name' => 'Дата и время',
                    'type' => 'S',
                    'user_type' => 'DateTime',
                    'required' => 'Y',
                    'sort' => 100,
                ],
                'PLACE' => [
                    'name' => 'Место проведения',
                    'type' => 'S',
                    'sort' => 110,
                    'searchable' => 'Y',
                ],
                'CATEGORY' => [
                    'name' => 'Категория',
                    'type' => 'L',
                    'sort' => 120,
                    'values' => [
                        ['xml_id' => 'literature', 'value' => 'Литература', 'sort' => 10],
                        ['xml_id' => 'children', 'value' => 'Для детей', 'sort' => 20],
                        ['xml_id' => 'tech', 'value' => 'Технологии', 'sort' => 30],
                        ['xml_id' => 'lecture', 'value' => 'Лекции', 'sort' => 40],
                    ],
                ],
                'FORMAT' => [
                    'name' => 'Формат',
                    'type' => 'L',
                    'sort' => 130,
                    'values' => [
                        ['xml_id' => 'offline', 'value' => 'Офлайн', 'sort' => 10],
                        ['xml_id' => 'online', 'value' => 'Онлайн', 'sort' => 20],
                        ['xml_id' => 'hybrid', 'value' => 'Гибрид', 'sort' => 30],
                    ],
                ],
                'SEATS' => [
                    'name' => 'Мест',
                    'type' => 'N',
                    'sort' => 140,
                ],
                'PRICE' => [
                    'name' => 'Стоимость',
                    'type' => 'S',
                    'sort' => 150,
                ],
                'DURATION' => [
                    'name' => 'Длительность',
                    'type' => 'S',
                    'sort' => 160,
                ],
                'REGISTER_URL' => [
                    'name' => 'Ссылка на регистрацию',
                    'type' => 'S',
                    'sort' => 170,
                ],
            ],
            'sections_data' => [
                ['code' => 'literature', 'name' => 'Литература', 'sort' => 100],
                ['code' => 'children', 'name' => 'Для детей', 'sort' => 110],
                ['code' => 'tech', 'name' => 'Технологии', 'sort' => 120],
                ['code' => 'lectures', 'name' => 'Лекции', 'sort' => 130],
            ],
        ],

        'collections' => [
            'type' => 'nlb_content',
            'name' => 'Подарочные наборы',
            'sort' => 120,
            'list_page_url' => '/collections/',
            'detail_page_url' => '/collections/#ELEMENT_CODE#/',
            'section_page_url' => '',
            'sections' => 'N',
            'properties' => [
                'COUNT' => [
                    'name' => 'Предметов в наборе',
                    'type' => 'N',
                    'sort' => 100,
                ],
                'COLOR' => [
                    'name' => 'Цвет карточки',
                    'type' => 'L',
                    'sort' => 110,
                    'values' => [
                        ['xml_id' => 'primary', 'value' => 'Синий', 'sort' => 10],
                        ['xml_id' => 'green', 'value' => 'Зелёный', 'sort' => 20],
                        ['xml_id' => 'sky', 'value' => 'Голубой', 'sort' => 30],
                        ['xml_id' => 'pink', 'value' => 'Розовый', 'sort' => 40],
                        ['xml_id' => 'orange', 'value' => 'Оранжевый', 'sort' => 50],
                    ],
                ],
                'ICON' => [
                    'name' => 'Иконка (код символа)',
                    'type' => 'S',
                    'sort' => 120,
                ],
            ],
            'sections_data' => [],
        ],

        'books' => [
            'type' => 'nlb_catalog',
            'name' => 'Сувенирная продукция',
            'sort' => 200,
            'list_page_url' => '/books/',
            'detail_page_url' => '/books/#ELEMENT_CODE#/',
            'section_page_url' => '/books/?section=#SECTION_CODE#',
            'sections' => 'Y',
            'properties' => [
                'AUTHOR' => [
                    'name' => 'Материал',
                    'type' => 'S',
                    'sort' => 100,
                    'searchable' => 'Y',
                ],
                'YEAR' => [
                    'name' => 'Размер',
                    'type' => 'S',
                    'sort' => 110,
                ],
                'ISBN' => [
                    'name' => 'Артикул',
                    'type' => 'S',
                    'sort' => 120,
                ],
                'PUBLISHER' => [
                    'name' => 'Упаковка',
                    'type' => 'S',
                    'sort' => 130,
                ],
                'PAGES' => [
                    'name' => 'Вес, г',
                    'type' => 'N',
                    'sort' => 140,
                ],
                'AGE_LIMIT' => [
                    'name' => 'Кому подойдёт',
                    'type' => 'L',
                    'sort' => 150,
                    'values' => [
                        ['xml_id' => 'all', 'value' => 'Всем', 'sort' => 10],
                        ['xml_id' => 'kids', 'value' => 'Детям', 'sort' => 20],
                        ['xml_id' => 'adult', 'value' => 'Взрослым', 'sort' => 30],
                    ],
                ],
                'AVAILABLE' => [
                    'name' => 'Наличие',
                    'type' => 'L',
                    'sort' => 160,
                    'values' => [
                        ['xml_id' => 'available', 'value' => 'В наличии', 'sort' => 10],
                        ['xml_id' => 'reserved', 'value' => 'Под заказ', 'sort' => 20],
                        ['xml_id' => 'unavailable', 'value' => 'Нет в наличии', 'sort' => 30],
                    ],
                ],
                'SHELF' => [
                    'name' => 'Состав',
                    'type' => 'S',
                    'sort' => 170,
                ],
                'PRICE' => [
                    'name' => 'Цена, ₽',
                    'type' => 'N',
                    'sort' => 180,
                ],
            ],
            'sections_data' => [
                ['code' => 'fiction', 'name' => 'Брелоки', 'sort' => 100],
                ['code' => 'history', 'name' => 'Посуда и фарфор', 'sort' => 110],
                ['code' => 'science', 'name' => 'Одежда', 'sort' => 120],
                ['code' => 'children', 'name' => 'Канцелярия', 'sort' => 130],
                ['code' => 'business', 'name' => 'Магниты и открытки', 'sort' => 140],
                ['code' => 'art', 'name' => 'Аксессуары', 'sort' => 150],
            ],
        ],

        'services' => [
            'type' => 'nlb_services',
            'name' => 'Услуги',
            'sort' => 300,
            'list_page_url' => '/services/',
            'detail_page_url' => '/services/#ELEMENT_CODE#/',
            'section_page_url' => '',
            'sections' => 'N',
            'properties' => [
                'ICON' => [
                    'name' => 'Иконка (код символа)',
                    'type' => 'S',
                    'sort' => 100,
                ],
                'COLOR' => [
                    'name' => 'Цвет',
                    'type' => 'L',
                    'sort' => 110,
                    'values' => [
                        ['xml_id' => 'primary', 'value' => 'Синий', 'sort' => 10],
                        ['xml_id' => 'green', 'value' => 'Зелёный', 'sort' => 20],
                        ['xml_id' => 'sky', 'value' => 'Голубой', 'sort' => 30],
                        ['xml_id' => 'pink', 'value' => 'Розовый', 'sort' => 40],
                        ['xml_id' => 'orange', 'value' => 'Оранжевый', 'sort' => 50],
                    ],
                ],
                'SUBTITLE' => [
                    'name' => 'Подзаголовок',
                    'type' => 'S',
                    'sort' => 115,
                ],
                'PRICE_FROM' => [
                    'name' => 'Цена от, ₽',
                    'type' => 'N',
                    'sort' => 120,
                ],
                'PRICE_LABEL' => [
                    'name' => 'Цена на карточке',
                    'type' => 'S',
                    'sort' => 125,
                ],
                'DURATION' => [
                    'name' => 'Режим / длительность',
                    'type' => 'S',
                    'sort' => 130,
                ],
                'SCHEDULE' => [
                    'name' => 'Режим работы (по строке на интервал)',
                    'type' => 'S',
                    'multiple' => 'Y',
                    'sort' => 135,
                ],
                'PHONE' => [
                    'name' => 'Телефон',
                    'type' => 'S',
                    'sort' => 136,
                ],
                'EMAIL' => [
                    'name' => 'E-mail',
                    'type' => 'S',
                    'sort' => 137,
                ],
                'FEATURES' => [
                    'name' => 'Что входит: заголовок|описание',
                    'type' => 'S',
                    'multiple' => 'Y',
                    'sort' => 140,
                ],
                'TARIFFS' => [
                    'name' => 'Тарифы: название|цена|примечание|что входит через ;|популярный',
                    'type' => 'S',
                    'multiple' => 'Y',
                    'sort' => 150,
                ],
            ],
            'sections_data' => [],
        ],

        'printing' => [
            'type' => 'nlb_services',
            'name' => 'Печатные услуги',
            'sort' => 310,
            'list_page_url' => '/printing/',
            'detail_page_url' => '/printing/#ELEMENT_CODE#/',
            'section_page_url' => '',
            'sections' => 'N',
            'properties' => [
                'ICON' => [
                    'name' => 'Иконка (код символа)',
                    'type' => 'S',
                    'sort' => 100,
                ],
                'COLOR' => [
                    'name' => 'Цвет',
                    'type' => 'L',
                    'sort' => 110,
                    'values' => [
                        ['xml_id' => 'primary', 'value' => 'Синий', 'sort' => 10],
                        ['xml_id' => 'green', 'value' => 'Зелёный', 'sort' => 20],
                        ['xml_id' => 'sky', 'value' => 'Голубой', 'sort' => 30],
                        ['xml_id' => 'pink', 'value' => 'Розовый', 'sort' => 40],
                        ['xml_id' => 'orange', 'value' => 'Оранжевый', 'sort' => 50],
                    ],
                ],
                'PRICE_FROM' => [
                    'name' => 'Цена от, ₽',
                    'type' => 'N',
                    'sort' => 120,
                ],
                'UNIT' => [
                    'name' => 'Единица измерения',
                    'type' => 'S',
                    'sort' => 130,
                ],
            ],
            'sections_data' => [],
        ],

        /* ------------------------------------------------------------------
         * Заявки с сайта. Это не контент, а рабочая база: элементы создают
         * посетители через bitrix:iblock.element.add.form. Поэтому
         *   - поисковый индекс выключен (персональные данные не должны
         *     попадать в поиск по сайту);
         *   - группа 2 («Все пользователи») получает право на запись,
         *     иначе анонимная форма не сможет добавить элемент;
         *   - user_data отключает предупреждение check.php об отсутствии
         *     демо-данных.
         * ------------------------------------------------------------------ */

        'requests' => [
            'type' => 'nlb_content',
            'name' => 'Обращения',
            'sort' => 400,
            'list_page_url' => '/feedback/',
            'detail_page_url' => '/feedback/',
            'section_page_url' => '',
            'sections' => 'N',
            'index' => 'N',
            'user_data' => true,
            'groups' => ['2' => 'W'],
            'properties' => [
                'EMAIL' => [
                    'name' => 'E-mail',
                    'type' => 'S',
                    'required' => 'Y',
                    'sort' => 100,
                ],
                'PHONE' => [
                    'name' => 'Телефон',
                    'type' => 'S',
                    'sort' => 110,
                ],
                'SUBJECT' => [
                    'name' => 'Тема',
                    'type' => 'S',
                    'sort' => 120,
                ],
                'MESSAGE' => [
                    'name' => 'Сообщение',
                    'type' => 'S',
                    'required' => 'Y',
                    'sort' => 130,
                ],
                'PAGE' => [
                    'name' => 'Страница-источник',
                    'type' => 'S',
                    'sort' => 140,
                ],
                'STATUS' => [
                    'name' => 'Статус',
                    'type' => 'L',
                    'sort' => 150,
                    'filtrable' => 'Y',
                    'values' => [
                        ['xml_id' => 'new', 'value' => 'Новое', 'sort' => 10, 'def' => true],
                        ['xml_id' => 'in_progress', 'value' => 'В работе', 'sort' => 20],
                        ['xml_id' => 'done', 'value' => 'Обработано', 'sort' => 30],
                    ],
                ],
            ],
            'sections_data' => [],
        ],

        'bookings' => [
            'type' => 'nlb_content',
            'name' => 'Заявки на услуги',
            'sort' => 410,
            'list_page_url' => '/services/',
            'detail_page_url' => '/services/',
            'section_page_url' => '',
            'sections' => 'N',
            'index' => 'N',
            'user_data' => true,
            'groups' => ['2' => 'W'],
            'properties' => [
                'SERVICE' => [
                    'name' => 'Услуга',
                    'type' => 'S',
                    'required' => 'Y',
                    'sort' => 100,
                    'searchable' => 'Y',
                ],
                'SERVICE_CODE' => [
                    'name' => 'Код услуги',
                    'type' => 'S',
                    'sort' => 110,
                    'filtrable' => 'Y',
                ],
                'TARIFF' => [
                    'name' => 'Тариф',
                    'type' => 'S',
                    'sort' => 120,
                ],
                'PRICE' => [
                    'name' => 'Стоимость по тарифу',
                    'type' => 'S',
                    'sort' => 130,
                ],
                'DATE' => [
                    'name' => 'Дата',
                    'type' => 'S',
                    'required' => 'Y',
                    'sort' => 140,
                    'filtrable' => 'Y',
                ],
                'TIME' => [
                    'name' => 'Время',
                    'type' => 'S',
                    'required' => 'Y',
                    'sort' => 150,
                ],
                'PERSON' => [
                    'name' => 'Имя посетителя',
                    'type' => 'S',
                    'required' => 'Y',
                    'sort' => 160,
                ],
                'PHONE' => [
                    'name' => 'Телефон',
                    'type' => 'S',
                    'required' => 'Y',
                    'sort' => 170,
                ],
                'EMAIL' => [
                    'name' => 'E-mail',
                    'type' => 'S',
                    'sort' => 180,
                ],
                'COMMENT' => [
                    'name' => 'Комментарий',
                    'type' => 'S',
                    'sort' => 190,
                ],
                'STATUS' => [
                    'name' => 'Статус',
                    'type' => 'L',
                    'sort' => 200,
                    'filtrable' => 'Y',
                    'values' => [
                        ['xml_id' => 'new', 'value' => 'Новая', 'sort' => 10, 'def' => true],
                        ['xml_id' => 'confirmed', 'value' => 'Подтверждена', 'sort' => 20],
                        ['xml_id' => 'cancelled', 'value' => 'Отменена', 'sort' => 30],
                    ],
                ],
            ],
            'sections_data' => [],
        ],

        'orders' => [
            'type' => 'nlb_content',
            'name' => 'Заказы сувениров',
            'sort' => 420,
            'list_page_url' => '/books/',
            'detail_page_url' => '/books/',
            'section_page_url' => '',
            'sections' => 'N',
            'index' => 'N',
            'user_data' => true,
            'groups' => ['2' => 'W'],
            'properties' => [
                'ITEM' => [
                    'name' => 'Товар',
                    'type' => 'S',
                    'required' => 'Y',
                    'sort' => 100,
                    'searchable' => 'Y',
                ],
                'ARTICLE' => [
                    'name' => 'Артикул',
                    'type' => 'S',
                    'sort' => 110,
                    'filtrable' => 'Y',
                ],
                'PRICE' => [
                    'name' => 'Цена, ₽',
                    'type' => 'N',
                    'sort' => 120,
                ],
                'QTY' => [
                    'name' => 'Количество',
                    'type' => 'N',
                    'required' => 'Y',
                    'sort' => 130,
                ],
                'PERSON' => [
                    'name' => 'Имя заказчика',
                    'type' => 'S',
                    'required' => 'Y',
                    'sort' => 140,
                ],
                'PHONE' => [
                    'name' => 'Телефон',
                    'type' => 'S',
                    'required' => 'Y',
                    'sort' => 150,
                ],
                'EMAIL' => [
                    'name' => 'E-mail',
                    'type' => 'S',
                    'sort' => 160,
                ],
                'COMMENT' => [
                    'name' => 'Комментарий',
                    'type' => 'S',
                    'sort' => 170,
                ],
                'STATUS' => [
                    'name' => 'Статус',
                    'type' => 'L',
                    'sort' => 180,
                    'filtrable' => 'Y',
                    'values' => [
                        ['xml_id' => 'new', 'value' => 'Новый', 'sort' => 10, 'def' => true],
                        ['xml_id' => 'processing', 'value' => 'В работе', 'sort' => 20],
                        ['xml_id' => 'done', 'value' => 'Выполнен', 'sort' => 30],
                        ['xml_id' => 'cancelled', 'value' => 'Отменён', 'sort' => 40],
                    ],
                ],
            ],
            'sections_data' => [],
        ],
    ],
];
