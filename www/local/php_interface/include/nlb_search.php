<?php
/**
 * NLB Bootstrap — поиск по сайту: индекс и ранжирование BM25.
 *
 * Мануал `Smart Site Search Manual.html` предлагает Python + FastAPI +
 * rank-bm25 + nltk. На shared-хостинге Битрикса Python нет, а на VPS это
 * второй процесс, который надо поднимать, обновлять и охранять. Сам алгоритм
 * при этом — арифметика: Okapi BM25 без модели, без GPU и без сети. Здесь он
 * перенесён на PHP, поэтому шаблон отдаёт релевантность там, где он и так
 * работает: `local/ajax/search.php` читает готовый индекс из `db/search/`.
 *
 * Что считается: стандартная формула Okapi BM25 с k1 = 1.5 и b = 0.75,
 * idf в варианте Lucene (`log(1 + (N - df + 0.5) / (df + 0.5))`), поэтому
 * вес всегда положительный, в отличие от rank-bm25, где idf может уходить
 * в минус на частых словах.
 *
 * Индекс собирается `tools/build-search-index.php` из демо-данных
 * (`db/config/data/*.php`, `db/config/iblocks.php`) и курируемых страниц
 * (`db/search/pages.json`). На живом ядре те же документы берутся из
 * инфоблоков: `nlb_search_documents_from_bitrix()`.
 *
 * Почему индекс лежит в `db/`: каталог закрыт от веба (`.htaccess` +
 * `robots.txt`), а поисковику индекс не нужен — он нужен только PHP.
 *
 * Паритет с браузером не нужен: вердикт один, считает сервер. Проверки —
 * `php tools/search-selftest.php` (фразы и математика) и раздел 11.2
 * в `tools/check-template.mjs` (файлы, эндпоинт, свежесть индекса).
 */

if (!function_exists('nlb_search_k1')) {
    /**
     * Насыщение частоты: выше k1 терм перестаёт усиливать документ.
     */
    function nlb_search_k1(): float
    {
        return 1.5;
    }
}

if (!function_exists('nlb_search_b')) {
    /**
     * Влияние длины документа: 0 — не влияет, 1 — нормирует полностью.
     */
    function nlb_search_b(): float
    {
        return 0.75;
    }
}

if (!function_exists('nlb_search_min_score')) {
    /**
     * Абсолютный порог: документ без заметного совпадения не показываем.
     * Ниже этого значения остаются совпадения по служебным словам вроде
     * «сайт», которые в индексе есть почти везде.
     */
    function nlb_search_min_score(): float
    {
        return 1.0;
    }
}

if (!function_exists('nlb_search_ratio')) {
    /**
     * Относительный порог: результат слабее лучшего настолько — шум.
     */
    function nlb_search_ratio(): float
    {
        return 0.35;
    }
}

if (!function_exists('nlb_search_limit')) {
    /**
     * Сколько результатов отдавать по умолчанию и максимум.
     */
    function nlb_search_limit(): int
    {
        return 5;
    }
}

if (!function_exists('nlb_search_max_limit')) {
    function nlb_search_max_limit(): int
    {
        return 10;
    }
}

if (!function_exists('nlb_search_query_length')) {
    /**
     * Длиннее — это уже не запрос, а попытка залить мусор в индекс.
     */
    function nlb_search_query_length(): int
    {
        return 200;
    }
}

if (!function_exists('nlb_search_stopwords')) {
    /**
     * Служебные слова: в индексе они есть у всех, веса не дают.
     * Список намеренно короткий — длинный срезает смысл запроса.
     */
    function nlb_search_stopwords(): array
    {
        return [
            'и', 'в', 'во', 'на', 'с', 'со', 'по', 'для', 'о', 'об', 'обо', 'от',
            'до', 'из', 'к', 'ко', 'у', 'не', 'ни', 'что', 'как', 'где', 'когда',
            'это', 'этот', 'эта', 'эти', 'а', 'но', 'или', 'же', 'ли', 'бы',
            'я', 'ты', 'он', 'она', 'оно', 'мы', 'вы', 'они', 'мне', 'меня',
            'есть', 'был', 'была', 'было', 'были', 'будет', 'можно', 'нужно',
            'the', 'a', 'an', 'of', 'to', 'in', 'and', 'or', 'is', 'are', 'for',
            'on', 'at', 'with', 'by', 'be', 'it', 'this', 'that',
        ];
    }
}

if (!function_exists('nlb_search_suffixes')) {
    /**
     * Окончания для усечения: без него «выставка» и «выставку» — разные
     * слова, и поиск промахивается мимо очевидного совпадения.
     *
     * Две группы, потому что цена ошибки разная. `noun` — падежные
     * окончания, режем на остатке от 3 букв. `verb` — спряжения и
     * возвратность, режем только на остатке от 4 букв: иначе «билет»
     * превращается в «бил», а «пакет» — в «пак».
     *
     * Сравнение идёт по самому длинному подходящему окончанию: короткое,
     * срезанное первым, слепило бы «выставку» и «выстав» в одно.
     */
    function nlb_search_suffixes(): array
    {
        return [
            'noun' => [
                'иями', 'ями', 'ами', 'ыми', 'ими', 'ого', 'его', 'ому', 'ему',
                'ов', 'ев', 'ий', 'ый', 'ой', 'ей', 'ая', 'яя', 'ое', 'ее',
                'ые', 'ие', 'ых', 'их', 'ым', 'им', 'ую', 'юю', 'ом', 'ем',
                'ам', 'ям', 'ах', 'ях', 'а', 'я', 'о', 'е', 'у', 'ю', 'ы', 'и', 'ь', 'й',
            ],
            'verb' => [
                'аетесь', 'яетесь', 'итесь', 'ается', 'яется', 'аются', 'яются',
                'уются', 'иться', 'аться', 'яться', 'етесь', 'ится', 'ется',
                'атся', 'ятся', 'утся', 'ться', 'тся', 'ете', 'ите', 'ешь', 'ишь',
                'ать', 'ять', 'ить', 'еть', 'ет', 'ут', 'ют', 'ат', 'ят',
            ],
        ];
    }
}

if (!function_exists('nlb_search_lower')) {
    /**
     * Нижний регистр, «ё» → «е», все разделители — в один пробел.
     */
    function nlb_search_lower(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = str_replace('ё', 'е', $value);
        $value = preg_replace('/[^a-zа-я0-9]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }
}

if (!function_exists('nlb_search_stem')) {
    /**
     * Усечение окончания. Возвращает основу, по которой идут и документ,
     * и запрос, — иначе «печать» и «печати» не встретятся.
     *
     * Второй проход режет только однобуквенные окончания. Иначе «мировых»
     * теряет «ых», а затем «ов», склеивается с «мир», и запрос «Война и мир»
     * находит «Концертный зал». Двух однобуквенных проходов хватает там, где
     * они нужны: «поступления» → «поступлени» → «поступлен».
     */
    function nlb_search_stem(string $token): string
    {
        $length = mb_strlen($token, 'UTF-8');
        if ($length < 4) {
            return $token;
        }

        $groups = nlb_search_suffixes();
        $minimums = ['verb' => 4, 'noun' => 3];

        $reflexive = mb_substr($token, -2, null, 'UTF-8');
        if ($reflexive === 'ся' || $reflexive === 'сь') {
            $before = mb_substr($token, -3, 1, 'UTF-8');
            if ($length >= 7 || ($before === 'т' && $length >= 5)) {
                $token = mb_substr($token, 0, $length - 2, 'UTF-8');
                $length = mb_strlen($token, 'UTF-8');
            }
        }

        for ($pass = 0; $pass < 2; $pass++) {
            $cut = '';
            $cutMinimum = 0;

            foreach ($minimums as $group => $minimum) {
                foreach ($groups[$group] as $suffix) {
                    $suffixLength = mb_strlen($suffix, 'UTF-8');
                    if ($pass === 1 && $suffixLength > 1) {
                        continue;
                    }
                    if ($suffixLength >= $length || $suffixLength <= mb_strlen($cut, 'UTF-8')) {
                        continue;
                    }
                    if ($length - $suffixLength < $minimum) {
                        continue;
                    }
                    if (mb_substr($token, -$suffixLength, null, 'UTF-8') === $suffix) {
                        $cut = $suffix;
                        $cutMinimum = $minimum;
                    }
                }
            }

            if ($cut === '' || $cutMinimum === 0) {
                break;
            }

            $candidate = mb_substr($token, 0, $length - mb_strlen($cut, 'UTF-8'), 'UTF-8');
            if (mb_strlen($candidate, 'UTF-8') < $cutMinimum) {
                break;
            }

            $token = $candidate;
            $length = mb_strlen($token, 'UTF-8');
        }

        return $token;
    }
}

if (!function_exists('nlb_search_tokenize')) {
    /**
     * Текст → основы слов без служебных. Повторы не схлопываются: они
     * нужны для частоты термина в документе.
     */
    function nlb_search_tokenize(string $text): array
    {
        $normalized = nlb_search_lower($text);
        if ($normalized === '') {
            return [];
        }

        $stopwords = nlb_search_stopwords();
        $tokens = [];
        foreach (explode(' ', $normalized) as $word) {
            if ($word === '' || in_array($word, $stopwords, true)) {
                continue;
            }
            if (mb_strlen($word, 'UTF-8') === 1 && !ctype_digit($word)) {
                continue;
            }
            $tokens[] = nlb_search_stem($word);
        }

        return $tokens;
    }
}

if (!function_exists('nlb_search_plain_text')) {
    /**
     * HTML инфоблока → текст для индекса и сниппета.
     */
    function nlb_search_plain_text(string $html): string
    {
        $text = str_replace(['<br>', '<br/>', '<br />', '</p>'], ' ', $html);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }
}

if (!function_exists('nlb_search_data_root')) {
    /**
     * Корень пакета: и консоль (`tools/`), и веб (`local/ajax/`) считают
     * его от DOCUMENT_ROOT, а тесты — от файла библиотеки.
     */
    function nlb_search_data_root(?string $root = null): string
    {
        if ($root !== null && $root !== '') {
            return rtrim($root, '/\\');
        }
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            return rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/\\');
        }

        return dirname(__DIR__, 3);
    }
}

if (!function_exists('nlb_search_index_path')) {
    function nlb_search_index_path(?string $root = null): string
    {
        return nlb_search_data_root($root) . '/db/search/index.json';
    }
}

if (!function_exists('nlb_search_pages_path')) {
    function nlb_search_pages_path(?string $root = null): string
    {
        return nlb_search_data_root($root) . '/db/search/pages.json';
    }
}

if (!function_exists('nlb_search_source_hashes')) {
    /**
     * Что считается источником индекса и каким был отпечаток каждого файла.
     * Пути относительные: отпечаток не должен зависеть от места, куда
     * распакован шаблон. Хеши пофайловые, а не один общий, — гейт тогда
     * говорит, какой именно файл разошёлся с индексом.
     */
    function nlb_search_source_hashes(?string $root = null): array
    {
        $root = nlb_search_data_root($root);
        $files = [];
        foreach (glob($root . '/db/config/data/*.php') ?: [] as $file) {
            $files[] = 'db/config/data/' . basename($file);
        }
        sort($files);
        $files[] = 'db/config/iblocks.php';
        $files[] = 'db/search/pages.json';

        $hashes = [];
        foreach ($files as $relative) {
            $absolute = $root . '/' . $relative;
            $hashes[$relative] = is_file($absolute)
                ? hash_file('sha256', $absolute)
                : '';
        }

        return $hashes;
    }
}

if (!function_exists('nlb_search_source_files')) {
    function nlb_search_source_files(?string $root = null): array
    {
        return array_keys(nlb_search_source_hashes($root));
    }
}

if (!function_exists('nlb_search_source_hash')) {
    /**
     * Один отпечаток всех источников. Сверяется в гейте: если данные
     * поправили, а индекс не пересобрали, поиск молча отдаёт старые
     * результаты — «свежесть» проверяется не по дате файла (дата врёт
     * при клоне), а по содержимому.
     */
    function nlb_search_source_hash(?string $root = null): string
    {
        return hash('sha256', (string)json_encode(nlb_search_source_hashes($root), JSON_UNESCAPED_SLASHES));
    }
}

if (!function_exists('nlb_search_property_text')) {
    /**
     * Свойства элемента, которые полезно искать: автор, год, место, теги.
     * Служебные (иконка, цвет) смысла не несут.
     */
    function nlb_search_property_text(array $properties): string
    {
        $allowed = [
            'AUTHOR', 'YEAR', 'PUBLISHER', 'ISBN', 'PLACE', 'DATE', 'PRICE',
            'PRICE_FROM', 'PRICE_LABEL', 'TAGS', 'DURATION', 'SCHEDULE',
            'SUBTITLE', 'FORMAT', 'CATEGORY',
        ];

        $parts = [];
        foreach ($properties as $key => $value) {
            if (!in_array((string)$key, $allowed, true)) {
                continue;
            }
            if (is_array($value)) {
                $value = implode(' ', array_map('strval', $value));
            }
            $value = nlb_search_plain_text((string)$value);
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return implode(' ', $parts);
    }
}

if (!function_exists('nlb_search_iblock_urls')) {
    /**
     * Шаблоны ссылок инфоблоков: `DETAIL_PAGE_URL` из `db/config/iblocks.php`
     * (раздел `iblocks`). Без ядра это единственный способ получить настоящие
     * адреса элементов.
     */
    function nlb_search_iblock_urls(?string $root = null): array
    {
        $root = nlb_search_data_root($root);
        $iblocks = nlb_search_iblocks_config($root);

        $urls = [];
        foreach ($iblocks as $code => $iblock) {
            if (!is_array($iblock)) {
                continue;
            }
            $urls[(string)$code] = [
                'detail' => (string)($iblock['detail_page_url'] ?? ''),
                'list' => (string)($iblock['list_page_url'] ?? ''),
            ];
        }

        return $urls;
    }
}

if (!function_exists('nlb_search_iblocks_config')) {
    /**
     * Конфиг инфоблоков: в `db/config/iblocks.php` он лежит под ключом
     * `iblocks`, рядом с `types`; читаем один раз и отдаём только блоки.
     */
    function nlb_search_iblocks_config(?string $root = null): array
    {
        static $cache = [];

        $root = nlb_search_data_root($root);
        if (array_key_exists($root, $cache)) {
            return $cache[$root];
        }

        $config = $root . '/db/config/iblocks.php';
        if (!is_file($config)) {
            return $cache[$root] = [];
        }

        $data = require $config;
        if (!is_array($data) || !isset($data['iblocks']) || !is_array($data['iblocks'])) {
            return $cache[$root] = [];
        }

        return $cache[$root] = $data['iblocks'];
    }
}

if (!function_exists('nlb_search_item_url')) {
    /**
     * Адрес элемента по шаблону инфоблока. Пустой `DETAIL_PAGE_URL` —
     * элемент живёт только в списке, ссылку не выдумываем.
     */
    function nlb_search_item_url(array $urls, string $iblock, string $code): string
    {
        $detail = (string)($urls[$iblock]['detail'] ?? '');
        $list = (string)($urls[$iblock]['list'] ?? '');
        if ($detail !== '' && $code !== '') {
            return str_replace('#ELEMENT_CODE#', $code, $detail);
        }

        return $list;
    }
}

if (!function_exists('nlb_search_documents_from_data')) {
    /**
     * Документы из демо-данных: 91 элемент книг, новостей, событий, услуг,
     * печати и наборов. Работает без ядра, поэтому годится и для офлайн-сборки,
     * и для самопроверки.
     */
    function nlb_search_documents_from_data(?string $root = null): array
    {
        $root = nlb_search_data_root($root);
        $urls = nlb_search_iblock_urls($root);
        $documents = [];

        foreach (glob($root . '/db/config/data/*.php') ?: [] as $file) {
            $data = require $file;
            if (!is_array($data) || !isset($data['iblock'], $data['items']) || !is_array($data['items'])) {
                continue;
            }

            $iblock = (string)$data['iblock'];
            foreach ($data['items'] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $code = (string)($item['code'] ?? '');
                $title = nlb_search_plain_text((string)($item['name'] ?? ''));
                if ($title === '') {
                    continue;
                }

                $xmlId = (string)($item['xml_id'] ?? '');
                $id = $iblock . ':' . ($xmlId !== '' ? $xmlId : $code);

                $text = trim(implode(' ', [
                    $title,
                    nlb_search_plain_text((string)($item['preview_text'] ?? '')),
                    nlb_search_plain_text((string)($item['detail_text'] ?? '')),
                    nlb_search_property_text(is_array($item['properties'] ?? null) ? $item['properties'] : []),
                ]));

                $documents[] = [
                    'id' => $id,
                    'title' => $title,
                    'url' => nlb_search_item_url($urls, $iblock, $code),
                    'category' => $iblock,
                    'text' => $text,
                ];
            }
        }

        return $documents;
    }
}

if (!function_exists('nlb_search_documents_from_pages')) {
    /**
     * Курируемые страницы: контакты, часы работы, правила записи. Тексты
     * лежат в `db/search/pages.json`, чтобы их можно было править без PHP.
     */
    function nlb_search_documents_from_pages(?string $root = null): array
    {
        $path = nlb_search_pages_path($root);
        if (!is_file($path)) {
            return [];
        }

        $data = json_decode((string)file_get_contents($path), true);
        if (!is_array($data) || !isset($data['pages']) || !is_array($data['pages'])) {
            return [];
        }

        $documents = [];
        foreach ($data['pages'] as $page) {
            if (!is_array($page)) {
                continue;
            }
            $title = nlb_search_plain_text((string)($page['title'] ?? ''));
            $text = nlb_search_plain_text((string)($page['text'] ?? ''));
            $url = (string)($page['url'] ?? '');
            $id = (string)($page['id'] ?? '');
            if ($title === '' || $text === '' || $url === '' || $id === '') {
                continue;
            }

            $documents[] = [
                'id' => $id,
                'title' => $title,
                'url' => $url,
                'category' => (string)($page['category'] ?? 'info'),
                'text' => trim($title . ' ' . $text),
            ];
        }

        return $documents;
    }
}

if (!function_exists('nlb_search_documents_from_bitrix')) {
    /**
     * Документы из инфоблоков на живом ядре. Вызывается сборщиком индекса
     * после установки Битрикса; офлайн возвращает пустой массив, чтобы
     * сборка не падала без ядра.
     *
     * Свойства читаются `GetProperty` отдельно на каждый элемент: это N+1
     * запрос, зато автор, теги и цена попадают в индекс так же, как в
     * демо-пути, — иначе после установки поиск был бы хуже демо. Сборка
     * идёт из CLI, на скорость ответа пользователю не влияет.
     */
    function nlb_search_documents_from_bitrix(?string $root = null): array
    {
        if (!class_exists('CIBlockElement') || !class_exists('CIBlock')) {
            return [];
        }

        $root = nlb_search_data_root($root);
        $iblocks = nlb_search_iblocks_config($root);
        if ($iblocks === []) {
            return [];
        }

        $documents = [];
        foreach ($iblocks as $code => $iblock) {
            if (!is_array($iblock)) {
                continue;
            }

            $code = (string)$code;
            $type = (string)($iblock['type'] ?? 'nlb_content');
            $found = CIBlock::GetList([], ['=CODE' => $code, '=TYPE' => $type])->Fetch();
            if (!is_array($found) || empty($found['ID'])) {
                continue;
            }

            $detail = (string)($iblock['detail_page_url'] ?? '');
            $list = (string)($iblock['list_page_url'] ?? '');

            $iterator = CIBlockElement::GetList(
                ['SORT' => 'ASC', 'NAME' => 'ASC'],
                ['IBLOCK_ID' => (int)$found['ID'], 'ACTIVE' => 'Y'],
                false,
                false,
                ['ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'DETAIL_PAGE_URL']
            );

            while ($element = $iterator->Fetch()) {
                $title = nlb_search_plain_text((string)$element['NAME']);
                if ($title === '') {
                    continue;
                }

                $url = (string)($element['DETAIL_PAGE_URL'] ?? '');
                if ($url === '') {
                    $url = $detail !== ''
                        ? str_replace('#ELEMENT_CODE#', (string)$element['CODE'], $detail)
                        : $list;
                }

                $properties = [];
                $propertyIterator = CIBlockElement::GetProperty((int)$found['ID'], (int)$element['ID']);
                while ($property = $propertyIterator->Fetch()) {
                    $propertyCode = (string)($property['CODE'] ?? '');
                    if ($propertyCode === '') {
                        continue;
                    }
                    $propertyValue = (string)($property['PROPERTY_TYPE'] ?? '') === 'L'
                        ? (string)($property['VALUE_ENUM'] ?? '')
                        : (string)($property['VALUE'] ?? '');
                    $propertyValue = nlb_search_plain_text($propertyValue);
                    if ($propertyValue !== '') {
                        $properties[$propertyCode][] = $propertyValue;
                    }
                }

                $text = trim(implode(' ', [
                    $title,
                    nlb_search_plain_text((string)$element['PREVIEW_TEXT']),
                    nlb_search_plain_text((string)$element['DETAIL_TEXT']),
                    nlb_search_property_text($properties),
                ]));

                $documents[] = [
                    'id' => $code . ':' . (string)$element['ID'],
                    'title' => $title,
                    'url' => $url,
                    'category' => $code,
                    'text' => $text,
                ];
            }
        }

        return $documents;
    }
}

if (!function_exists('nlb_search_build_index')) {
    /**
     * Документы → индекс: длина каждого документа, средняя длина, обращения
     * терма. Обращения хранятся парами `[индекс документа, частота]`, чтобы
     * файл оставался компактным и читаемым.
     */
    function nlb_search_build_index(array $documents, ?string $root = null): array
    {
        $root = nlb_search_data_root($root);

        $docs = [];
        $terms = [];
        $totalLength = 0;

        foreach (array_values($documents) as $position => $document) {
            $tokenized = nlb_search_tokenize((string)($document['text'] ?? ''));
            $length = count($tokenized);
            $totalLength += $length;

            $frequency = array_count_values($tokenized);
            arsort($frequency);
            foreach ($frequency as $term => $count) {
                $terms[(string)$term][] = [$position, (int)$count];
            }

            $docs[] = [
                'id' => (string)($document['id'] ?? 'doc-' . $position),
                'title' => (string)($document['title'] ?? ''),
                'url' => (string)($document['url'] ?? ''),
                'category' => (string)($document['category'] ?? ''),
                'dl' => $length,
                'text' => (string)($document['text'] ?? ''),
            ];
        }

        $count = count($docs);
        ksort($terms);

        return [
            'version' => 1,
            'built_at' => date('c'),
            'source_hash' => nlb_search_source_hash($root),
            'sources' => nlb_search_source_hashes($root),
            'k1' => nlb_search_k1(),
            'b' => nlb_search_b(),
            'docs_count' => $count,
            'avgdl' => $count > 0 ? round($totalLength / $count, 3) : 0.0,
            'docs' => $docs,
            'terms' => $terms,
        ];
    }
}

if (!function_exists('nlb_search_save_index')) {
    /**
     * Запись индекса. Каталог создаётся при необходимости; пишем во
     * временный файл и переименовываем, чтобы читатель не увидел
     * половину JSON во время пересборки.
     */
    function nlb_search_save_index(array $index, ?string $root = null): bool
    {
        $path = nlb_search_index_path($root);
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        $json = json_encode($index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            return false;
        }

        $temp = $path . '.tmp';
        if (file_put_contents($temp, $json . "\n") === false) {
            return false;
        }

        if (!rename($temp, $path)) {
            @unlink($temp);
            return false;
        }

        return true;
    }
}

if (!function_exists('nlb_search_load_index')) {
    /**
     * Чтение индекса. Битый или отсутствующий файл — `null`, а не исключение:
     * эндпоинт должен ответить 503 и оставить штатный поиск живым.
     */
    function nlb_search_load_index(?string $root = null): ?array
    {
        $path = nlb_search_index_path($root);
        if (!is_file($path)) {
            return null;
        }

        $data = json_decode((string)file_get_contents($path), true);
        if (!is_array($data) || empty($data['docs']) || !isset($data['terms'], $data['avgdl'])) {
            return null;
        }

        return $data;
    }
}

if (!function_exists('nlb_search_snippet')) {
    /**
     * Сниппет: окно вокруг первого вхождения слова запроса. Сравнивать
     * основы с текстом нельзя (в тексте окончания), поэтому ищем исходные
     * слова запроса, а если не нашли — отдаём начало текста.
     */
    function nlb_search_snippet(string $text, string $query, int $length = 140): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $haystack = mb_strtolower(str_replace('ё', 'е', $text), 'UTF-8');
        $position = false;

        foreach (explode(' ', nlb_search_lower($query)) as $word) {
            if (mb_strlen($word, 'UTF-8') < 3) {
                continue;
            }
            $found = mb_strpos($haystack, $word, 0, 'UTF-8');
            if ($found !== false && ($position === false || $found < $position)) {
                $position = $found;
            }
        }

        if ($position === false || $position < 40) {
            $position = 0;
        } else {
            $position = $position - 40;
        }

        $snippet = mb_substr($text, $position, $length, 'UTF-8');
        if ($position > 0) {
            $snippet = '…' . $snippet;
        }
        if ($position + $length < mb_strlen($text, 'UTF-8')) {
            $snippet .= '…';
        }

        return $snippet;
    }
}

if (!function_exists('nlb_search_coord')) {
    /**
     * Насколько документ покрывает запрос: доля слов запроса, которые в нём
     * есть. Это coord-фактор классической модели Lucene — он опускает ниже
     * документы, совпавшие одним словом из трёх, но не выбрасывает их совсем.
     *
     * Жёсткое требование «все слова» здесь не годится: «купить кружку» не
     * нашлось бы ни разу, потому что в каталоге пишут «цена», а не «купить».
     */
    function nlb_search_coord(int $matched, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return max(0.0, min(1.0, $matched / $total));
    }
}

if (!function_exists('nlb_search_query')) {
    /**
     * Ранжирование: Okapi BM25. Возвращает список `[id, title, url,
     * category, score, snippet]`, отсортированный по убыванию оценки.
     *
     * Термы запроса не дублируются: «книга книга книга» не должно получать
     * втрое больший вес, чем «книга».
     */
    function nlb_search_query(array $index, string $query, int $limit = 5): array
    {
        $limit = max(1, min($limit, nlb_search_max_limit()));
        $tokens = array_values(array_unique(nlb_search_tokenize($query)));
        if ($tokens === []) {
            return [];
        }

        $docs = $index['docs'] ?? [];
        $terms = $index['terms'] ?? [];
        $count = (int)($index['docs_count'] ?? count($docs));
        $avgdl = (float)($index['avgdl'] ?? 0.0);
        if ($count === 0 || $avgdl <= 0.0) {
            return [];
        }

        $k1 = (float)($index['k1'] ?? nlb_search_k1());
        $b = (float)($index['b'] ?? nlb_search_b());

        $scores = [];
        $matched = [];
        foreach ($tokens as $token) {
            if (!isset($terms[$token]) || !is_array($terms[$token])) {
                continue;
            }

            $postings = $terms[$token];
            $df = count($postings);
            $idf = log(1 + ($count - $df + 0.5) / ($df + 0.5));

            foreach ($postings as $posting) {
                if (!is_array($posting) || count($posting) < 2) {
                    continue;
                }
                $position = (int)$posting[0];
                $tf = (int)$posting[1];
                if ($tf <= 0 || !isset($docs[$position])) {
                    continue;
                }

                $dl = (float)($docs[$position]['dl'] ?? 0);
                $denominator = $tf + $k1 * (1 - $b + $b * $dl / $avgdl);
                if ($denominator <= 0.0) {
                    continue;
                }

                $scores[$position] = ($scores[$position] ?? 0.0) + $idf * ($tf * ($k1 + 1)) / $denominator;
                $matched[$position] = ($matched[$position] ?? 0) + 1;
            }
        }

        if ($scores === []) {
            return [];
        }

        $total = count($tokens);
        $coord = [];
        foreach ($scores as $position => $score) {
            $coord[$position] = $score * nlb_search_coord((int)($matched[$position] ?? 0), $total);
        }

        $best = max($coord);
        $floor = max(nlb_search_min_score(), $best * nlb_search_ratio());

        $ranked = [];
        foreach ($coord as $position => $score) {
            if ($score < $floor) {
                continue;
            }
            $ranked[] = ['position' => (int)$position, 'score' => (float)$score];
        }

        usort($ranked, static function (array $left, array $right) use ($docs): int {
            if ($left['score'] === $right['score']) {
                $leftLength = (int)($docs[$left['position']]['dl'] ?? 0);
                $rightLength = (int)($docs[$right['position']]['dl'] ?? 0);
                if ($leftLength !== $rightLength) {
                    return $leftLength <=> $rightLength;
                }

                return strcmp(
                    (string)($docs[$left['position']]['id'] ?? ''),
                    (string)($docs[$right['position']]['id'] ?? '')
                );
            }

            return $right['score'] <=> $left['score'];
        });

        $results = [];
        foreach (array_slice($ranked, 0, $limit) as $item) {
            $document = $docs[$item['position']];
            $results[] = [
                'id' => (string)($document['id'] ?? ''),
                'title' => (string)($document['title'] ?? ''),
                'url' => (string)($document['url'] ?? ''),
                'category' => (string)($document['category'] ?? ''),
                'score' => round($item['score'], 3),
                'snippet' => nlb_search_snippet((string)($document['text'] ?? ''), $query),
            ];
        }

        return $results;
    }
}

if (!function_exists('nlb_search_api')) {
    /**
     * Контракт эндпоинта `local/ajax/search.php` без HTTP: метод, параметры
     * и корень на входе — статус и тело ответа на выходе.
     *
     * Так проверяются 400/405/503 в `php tools/search-selftest.php`, а не
     * только живым запросом: сам эндпоинт остаётся тонкой обёрткой, которой
     * остаётся отдать заголовки и `json_encode`.
     *
     * Порядок проверок важен: метод раньше параметров (на POST бессмысленно
     * разбирать `q`), пустой запрос раньше длины.
     */
    function nlb_search_api(array $params, string $method, ?string $root = null): array
    {
        if ($method !== 'GET') {
            return ['status' => 405, 'body' => ['ok' => false, 'error' => 'method_not_allowed']];
        }

        $query = trim((string)($params['q'] ?? ''));
        if ($query === '') {
            return ['status' => 400, 'body' => ['ok' => false, 'error' => 'empty_query']];
        }

        if (mb_strlen($query, 'UTF-8') > nlb_search_query_length()) {
            return ['status' => 400, 'body' => ['ok' => false, 'error' => 'query_too_long']];
        }

        $index = nlb_search_load_index($root);
        if ($index === null) {
            return ['status' => 503, 'body' => ['ok' => false, 'error' => 'index_unavailable']];
        }

        $limit = (int)($params['limit'] ?? nlb_search_limit());
        if ($limit <= 0) {
            $limit = nlb_search_limit();
        }

        $results = nlb_search_query($index, $query, $limit);

        return [
            'status' => 200,
            'body' => [
                'ok' => true,
                'query' => $query,
                'count' => count($results),
                'results' => $results,
            ],
        ];
    }
}
