-- ==========================================================================
-- NLB Bootstrap — SQL-проверки после миграций.
--
-- Это не установка структуры: инфоблоки, свойства и элементы создаются
-- миграциями (db/run.php), потому что только API Битрикса корректно ведёт
-- счётчики b_iblock_sequence, кеш и таблицы свойств версии 2.
-- Здесь — контрольные запросы для phpMyAdmin/HeidiSQL.
--
-- Перед запуском:
--   1) выберите базу сайта;
--   2) если в конфигурации задан префикс таблиц, замените b_ на свой
--      (например, s1_b_iblock);
--   3) ожидаемые значения — в комментариях.
-- ==========================================================================

-- 1. Типы инфоблоков. Ожидается 3 строки: nlb_content, nlb_catalog, nlb_services.
SELECT ID, SORT
FROM b_iblock_type
WHERE ID LIKE 'nlb\_%'
ORDER BY SORT;

-- 2. Инфоблоки. Ожидается 6 строк: news, events, collections, books,
--    services, printing. ACTIVE = Y.
SELECT b.ID, b.CODE, b.NAME, b.IBLOCK_TYPE_ID, b.ACTIVE, b.VERSION
FROM b_iblock b
WHERE b.CODE IN ('news', 'events', 'collections', 'books', 'services', 'printing')
ORDER BY b.SORT;

-- 3. Свойства. Для news: AUTHOR, TAGS, READ_TIME, SOURCE_URL.
SELECT b.CODE AS IBLOCK, p.CODE, p.PROPERTY_TYPE, p.MULTIPLE,
       p.USER_TYPE, p.IS_REQUIRED, p.SORT
FROM b_iblock_property p
JOIN b_iblock b ON b.ID = p.IBLOCK_ID
WHERE b.CODE IN ('news', 'events', 'collections', 'books', 'services', 'printing')
ORDER BY b.CODE, p.SORT;

-- 4. Значения списков. У books.AGE_LIMIT — 5 значений, у services.COLOR — 5.
SELECT b.CODE AS IBLOCK, p.CODE AS PROPERTY, e.ID, e.VALUE, e.XML_ID, e.SORT
FROM b_iblock_property_enum e
JOIN b_iblock_property p ON p.ID = e.PROPERTY_ID
JOIN b_iblock b ON b.ID = p.IBLOCK_ID
WHERE b.CODE IN ('news', 'events', 'collections', 'books', 'services', 'printing')
ORDER BY b.CODE, p.SORT, e.SORT;

-- 5. Количество элементов. Ожидается: news 10, events 6, collections 6,
--    books 12, services 6, printing 8.
SELECT b.CODE, COUNT(e.ID) AS ELEMENTS
FROM b_iblock b
LEFT JOIN b_iblock_element e ON e.IBLOCK_ID = b.ID AND e.ACTIVE = 'Y'
WHERE b.CODE IN ('news', 'events', 'collections', 'books', 'services', 'printing')
GROUP BY b.CODE
ORDER BY b.CODE;

-- 6. Разделы. news — 2, events — 4, books — 6.
SELECT b.CODE AS IBLOCK, COUNT(s.ID) AS SECTIONS
FROM b_iblock b
LEFT JOIN b_iblock_section s ON s.IBLOCK_ID = b.ID AND s.ACTIVE = 'Y'
WHERE b.CODE IN ('news', 'events', 'books')
GROUP BY b.CODE
ORDER BY b.CODE;

-- 7. Дубли XML_ID внутри инфоблока. Должно быть 0 строк.
SELECT IBLOCK_ID, XML_ID, COUNT(*) AS CNT
FROM b_iblock_element
WHERE XML_ID LIKE 'nlb-%'
GROUP BY IBLOCK_ID, XML_ID
HAVING COUNT(*) > 1;

-- 8. Элементы без привязки к разделу (для news/events/books ожидается 0).
SELECT b.CODE AS IBLOCK, e.ID, e.NAME
FROM b_iblock_element e
JOIN b_iblock b ON b.ID = e.IBLOCK_ID
WHERE b.CODE IN ('news', 'events', 'books')
  AND e.IBLOCK_SECTION_ID IS NULL
  AND e.XML_ID LIKE 'nlb-%';

-- 9. Привязка шаблона к сайту.
SELECT SITE_ID, TEMPLATE, CONDITION, SORT
FROM b_site_template
ORDER BY SITE_ID, SORT;

-- 10. Счётчик следующего ID элементов. Если он меньше MAX(ID) в
--     b_iblock_element, новые элементы из админки могут получить занятый ID.
SELECT b.CODE, s.IBLOCK_ID, s.SEQUENCE
FROM b_iblock_sequence s
JOIN b_iblock b ON b.ID = s.IBLOCK_ID
WHERE b.CODE IN ('news', 'events', 'collections', 'books', 'services', 'printing');
