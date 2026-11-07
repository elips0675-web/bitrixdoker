<?php
/**
 * Самопроверка поиска по сайту (BM25).
 *
 * Запуск: php tools/search-selftest.php
 *
 * Что проверяется:
 *  - индекс `db/search/index.json` читается, целостен и собран из текущих
 *    источников (правка `db/config/data/*.php` без пересборки — падение);
 *  - токенизация и усечение окончаний: падежи и спряжения склеиваются,
 *    короткие слова не калечатся («билет» не становится «бил»);
 *  - математика BM25: редкое слово весит больше частого, длинный документ
 *    при той же частоте получает меньше, пустой запрос — пустая выдача;
 *  - запросы из `db/tests/fixtures/search-cases.json` дают ожидаемый
 *    первый результат, ожидаемый документ в выдаче или пустую выдачу.
 *
 * Выход: 0 — все проверки прошли, 1 — есть падения.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

define('B_PROLOG_INCLUDED', true);

$root = dirname(__DIR__);

require __DIR__ . '/../local/php_interface/include/nlb_search.php';

$checks = [];
$failures = [];

$check = static function (bool $condition, string $message) use (&$checks, &$failures): void {
    if ($condition) {
        $checks[] = $message;
        return;
    }
    $failures[] = $message;
};

/* ------------------------------------------------------------------ */
/* 1. Индекс: есть, читается, соответствует источникам                 */
/* ------------------------------------------------------------------ */

$index = nlb_search_load_index($root);
$check($index !== null, 'индекс db/search/index.json читается');

if ($index === null) {
    echo "Провалено: индекса нет. Соберите: php tools/build-search-index.php\n";
    exit(1);
}

$stored = is_array($index['sources'] ?? null) ? $index['sources'] : [];
$actual = nlb_search_source_hashes($root);
$check($stored === $actual, 'индекс соответствует источникам (не устарел)');

if ($stored !== $actual) {
    $diff = [];
    foreach ($actual as $relative => $hash) {
        if (($stored[$relative] ?? null) !== $hash) {
            $diff[] = $relative;
        }
    }
    if ($diff !== []) {
        $failures[] = 'разошлись источники: ' . implode(', ', $diff)
            . ' — пересоберите php tools/build-search-index.php';
    }
}

$documents = $index['docs'] ?? [];
$check($documents !== [], 'в индексе есть документы');
$check((int)($index['docs_count'] ?? 0) === count($documents), 'docs_count совпадает с числом документов');
$check((float)($index['avgdl'] ?? 0) > 0.0, 'средняя длина документа больше нуля');
$check(abs((float)($index['k1'] ?? 0) - nlb_search_k1()) < 0.0001, 'k1 в индексе равен константе 1.5');
$check(abs((float)($index['b'] ?? 0) - nlb_search_b()) < 0.0001, 'b в индексе равен константе 0.75');

$ids = [];
$badUrl = 0;
$badText = 0;
$badLength = 0;
foreach ($documents as $document) {
    $ids[] = (string)($document['id'] ?? '');
    $url = (string)($document['url'] ?? '');
    if ($url === '' || $url[0] !== '/' || str_contains($url, '#ELEMENT_CODE#')) {
        $badUrl++;
    }
    if (trim((string)($document['text'] ?? '')) === '') {
        $badText++;
    }
    if ((int)($document['dl'] ?? 0) <= 0) {
        $badLength++;
    }
}

$check(count($ids) === count(array_unique($ids)), 'идентификаторы документов уникальны');
$check($badUrl === 0, 'у каждого документа есть внутренний адрес без шаблонных меток');
$check($badText === 0, 'у каждого документа непустой текст');
$check($badLength === 0, 'у каждого документа посчитана длина');

$terms = is_array($index['terms'] ?? null) ? $index['terms'] : [];
$check($terms !== [], 'в индексе есть словарь термов');

$badPosting = 0;
$limit = count($documents);
foreach ($terms as $postings) {
    foreach ((array)$postings as $posting) {
        $position = (int)($posting[0] ?? -1);
        $tf = (int)($posting[1] ?? 0);
        if ($position < 0 || $position >= $limit || $tf <= 0) {
            $badPosting++;
        }
    }
}
$check($badPosting === 0, 'все обращения указывают на существующие документы');

$fromData = nlb_search_documents_from_data($root);
$fromPages = nlb_search_documents_from_pages($root);
$check(
    count($fromData) + count($fromPages) === count($documents),
    'индекс собран из всех источников: элементы(' . count($fromData) . ') + страницы(' . count($fromPages) . ')'
);

/* ------------------------------------------------------------------ */
/* 2. Токенизация и усечение окончаний                                */
/* ------------------------------------------------------------------ */

$check(
    nlb_search_stem('выставка') === nlb_search_stem('выставку'),
    'падежи склеиваются: «выставка» = «выставку»'
);
$check(
    nlb_search_stem('книга') === nlb_search_stem('книги'),
    'падежи склеиваются: «книга» = «книги»'
);
$check(
    nlb_search_stem('билет') === nlb_search_stem('билеты'),
    'падежи склеиваются: «билет» = «билеты»'
);
$check(nlb_search_stem('билет') === 'билет', 'короткое слово не калечится: «билет» остаётся «билет»');
$check(nlb_search_stem('пакет') === 'пакет', 'короткое слово не калечится: «пакет» остаётся «пакет»');
$check(
    nlb_search_stem('закрываетесь') === nlb_search_stem('закрываются'),
    'спряжения склеиваются: «закрываетесь» = «закрываются»'
);
$check(
    nlb_search_stem('записаться') === nlb_search_stem('запись'),
    'глагол и существительное склеиваются: «записаться» = «запись»'
);
$check(
    nlb_search_stem('мировых') === 'миров',
    'прилагательное не теряет «ов» на втором проходе: «мировых» остаётся «миров»'
);
$check(
    nlb_search_stem('мировых') !== nlb_search_stem('мир'),
    'стем «миров» не склеивается с «мир»: «Война и мир» не находит концертный зал'
);
$check(
    nlb_search_tokenize('Ёлка и книга') === nlb_search_tokenize('елка книга'),
    '«ё» приводится к «е», служебные слова выбрасываются'
);
$check(nlb_search_tokenize('как где когда') === [], 'запрос из одних служебных слов — пустой');
$check(
    nlb_search_tokenize(nlb_search_plain_text('<p>Печать</p>')) === ['печат'],
    'HTML вычищается до текста перед разбором'
);
$check(
    nlb_search_plain_text('<p>Привет<br>мир &laquo;книга&raquo;</p>') === 'Привет мир «книга»',
    'HTML-теги сняты, сущности раскрыты, пробелы схлопнуты'
);

/* ------------------------------------------------------------------ */
/* 3. Математика BM25 на синтетическом индексе                        */
/* ------------------------------------------------------------------ */

$synthetic = [];
for ($i = 1; $i <= 10; $i++) {
    $synthetic[] = [
        'id' => 'filler:' . $i,
        'title' => 'Заполнитель ' . $i,
        'url' => '/filler/' . $i . '/',
        'category' => 'filler',
        'text' => 'заполнитель номер ' . $i,
    ];
}
$synthetic[] = [
    'id' => 'test:short',
    'title' => 'Короткий',
    'url' => '/test/short/',
    'category' => 'test',
    'text' => 'слово',
];
$synthetic[] = [
    'id' => 'test:long',
    'title' => 'Длинный',
    'url' => '/test/long/',
    'category' => 'test',
    'text' => 'слово много разных других терминов ещё',
];
$synthetic[] = [
    'id' => 'test:both',
    'title' => 'Оба слова',
    'url' => '/test/both/',
    'category' => 'test',
    'text' => 'альфа бета',
];
$synthetic[] = [
    'id' => 'test:one',
    'title' => 'Одно слово',
    'url' => '/test/one/',
    'category' => 'test',
    'text' => 'альфа',
];
$synthetic[] = [
    'id' => 'test:rare',
    'title' => 'Редкое слово',
    'url' => '/test/rare/',
    'category' => 'test',
    'text' => 'редкое',
];
for ($i = 1; $i <= 3; $i++) {
    $synthetic[] = [
        'id' => 'test:common' . $i,
        'title' => 'Частое слово ' . $i,
        'url' => '/test/common' . $i . '/',
        'category' => 'test',
        'text' => 'частое',
    ];
}

$syntheticIndex = nlb_search_build_index($synthetic, $root);

$shortResults = nlb_search_query($syntheticIndex, 'слово', 10);
$shortScore = 0.0;
$longScore = 0.0;
foreach ($shortResults as $result) {
    if ($result['id'] === 'test:short') {
        $shortScore = (float)$result['score'];
    }
    if ($result['id'] === 'test:long') {
        $longScore = (float)$result['score'];
    }
}
$check($shortScore > 0.0 && $longScore > 0.0, 'документы с совпадением попадают в выдачу');
$check($shortScore > $longScore, 'длинный документ при той же частоте весит меньше короткого');

$coordResults = nlb_search_query($syntheticIndex, 'альфа бета', 10);
$check(
    ($coordResults[0]['id'] ?? '') === 'test:both',
    'документ, покрывающий два слова запроса, выше документа с одним'
);

$rareResults = nlb_search_query($syntheticIndex, 'редкое', 10);
$commonResults = nlb_search_query($syntheticIndex, 'частое', 10);
$rareBest = (float)($rareResults[0]['score'] ?? 0.0);
$commonBest = (float)($commonResults[0]['score'] ?? 0.0);
$check($rareBest > 0.0 && $commonBest > 0.0, 'редкое и частое слово дают выдачу');
$check($rareBest > $commonBest, 'редкое слово весит больше частого');

$check(nlb_search_query($syntheticIndex, '', 10) === [], 'пустой запрос — пустая выдача');
$check(nlb_search_query($syntheticIndex, 'как где', 10) === [], 'запрос из служебных слов — пустая выдача');
$check(nlb_search_query($syntheticIndex, 'ываыва', 10) === [], 'незнакомое слово — пустая выдача');
$check(count(nlb_search_query($syntheticIndex, 'частое', 2)) === 2, 'limit ограничивает выдачу');
$check(count(nlb_search_query($syntheticIndex, 'частое', 100)) <= nlb_search_max_limit(), 'limit не превышает максимум');

$firstScore = (float)($shortResults[0]['score'] ?? 0.0);
$check($firstScore > 0.0 && $firstScore <= 100.0, 'оценка релевантности положительная и конечная');

/* ------------------------------------------------------------------ */
/* 4. Сниппет                                                         */
/* ------------------------------------------------------------------ */

$check(
    str_contains(nlb_search_snippet('Библиотека открыта ежедневно с 10:00 до 22:00', 'ежедневно'), 'ежедневно'),
    'сниппет содержит слово запроса'
);
$longText = str_repeat('Книги и журналы. ', 40) . 'периодика';
$longSnippet = nlb_search_snippet($longText, 'периодика', 100);
$check(mb_strlen($longSnippet, 'UTF-8') <= 102, 'длинный сниппет обрезан');
$check(str_contains($longSnippet, '…'), 'обрезанный сниппет помечен многоточием');
$check(nlb_search_snippet('', 'слово') === '', 'пустой текст — пустой сниппет');

/* ------------------------------------------------------------------ */
/* 5. Запросы из набора фраз                                          */
/* ------------------------------------------------------------------ */

$fixturePath = $root . '/db/tests/fixtures/search-cases.json';
$fixture = is_file($fixturePath)
    ? json_decode((string)file_get_contents($fixturePath), true)
    : null;

$check(is_array($fixture) && isset($fixture['cases']) && is_array($fixture['cases']), 'набор запросов читается');

$cases = is_array($fixture) && isset($fixture['cases']) ? $fixture['cases'] : [];
$check(count($cases) >= 20, 'в наборе не меньше 20 запросов (сейчас ' . count($cases) . ')');

$emptyChecked = 0;
$topChecked = 0;
$containsChecked = 0;

foreach ($cases as $case) {
    if (!is_array($case)) {
        $failures[] = 'набор запросов: элемент не объект';
        continue;
    }

    $query = (string)($case['query'] ?? '');
    $label = $query === '' ? '(пустой запрос)' : $query;
    $results = nlb_search_query($index, $query, nlb_search_max_limit());

    if (array_key_exists('top', $case) && $case['top'] === null) {
        $emptyChecked++;
        $check($results === [], '«' . $label . '» — выдача пуста');
        continue;
    }

    if (isset($case['top'])) {
        $topChecked++;
        $check(
            ($results[0]['id'] ?? '') === (string)$case['top'],
            '«' . $label . '» — первый результат ' . (string)$case['top']
                . ' (получено «' . (string)($results[0]['id'] ?? 'ничего') . '»)'
        );
    }

    if (isset($case['contains']) && is_array($case['contains'])) {
        $containsChecked++;
        $found = array_column($results, 'id');
        foreach ($case['contains'] as $expected) {
            $check(
                in_array((string)$expected, $found, true),
                '«' . $label . '» — в выдаче есть ' . (string)$expected
            );
        }
    }

    foreach ($results as $result) {
        $check(
            (float)$result['score'] > 0.0 && (string)$result['url'] !== '',
            '«' . $label . '» — у результата есть оценка и адрес'
        );
        break;
    }
}

$check($topChecked > 0, 'набор проверяет первый результат (' . $topChecked . ')');
$check($containsChecked > 0, 'набор проверяет присутствие документа (' . $containsChecked . ')');
$check($emptyChecked > 0, 'набор проверяет пустую выдачу (' . $emptyChecked . ')');

/* ------------------------------------------------------------------ */
/* 6. Контракт эндпоинта (nlb_search_api)                             */
/* ------------------------------------------------------------------ */

$api = static function (array $params, string $method = 'GET', ?string $at = null) use ($root): array {
    return nlb_search_api($params, $method, $at ?? $root);
};

$wrongMethod = $api(['q' => 'часы работы'], 'POST');
$check($wrongMethod['status'] === 405, 'POST → 405 method_not_allowed');
$check(($wrongMethod['body']['error'] ?? '') === 'method_not_allowed', 'POST → код ошибки method_not_allowed');

$emptyQuery = $api([]);
$check($emptyQuery['status'] === 400, 'без q → 400');
$check(($emptyQuery['body']['error'] ?? '') === 'empty_query', 'без q → empty_query');
$check(($api(['q' => '   '])['status'] ?? 0) === 400, 'q из пробелов → 400');

$tooLong = $api(['q' => str_repeat('я', nlb_search_query_length() + 1)]);
$check($tooLong['status'] === 400, 'q длиннее ' . nlb_search_query_length() . ' → 400');
$check(($tooLong['body']['error'] ?? '') === 'query_too_long', 'длинный q → query_too_long');

$ok = $api(['q' => 'часы работы']);
$check($ok['status'] === 200, 'GET q=часы работы → 200');
$check(($ok['body']['ok'] ?? false) === true, 'успешный ответ помечен ok');
$check(($ok['body']['query'] ?? '') === 'часы работы', 'запрос возвращается как пришёл');
$check(($ok['body']['count'] ?? -1) === count($ok['body']['results'] ?? []), 'count совпадает с числом результатов');
$check((($ok['body']['results'][0]['id'] ?? '') === 'page:hours'), 'GET q=часы работы → первый page:hours');

$one = $api(['q' => 'печать', 'limit' => '1']);
$check(($one['status'] ?? 0) === 200 && count($one['body']['results'] ?? []) === 1, 'limit=1 отдаёт один результат');

$many = $api(['q' => 'печать', 'limit' => '100']);
$check(count($many['body']['results'] ?? []) <= nlb_search_max_limit(), 'limit больше максимума не пробивает потолок');

$missing = $api(['q' => 'часы работы'], 'GET', rtrim(sys_get_temp_dir(), '/\\') . '/nlb-search-no-index');
$check($missing['status'] === 503, 'нет индекса → 503');
$check(($missing['body']['error'] ?? '') === 'index_unavailable', 'нет индекса → index_unavailable');

/* ------------------------------------------------------------------ */
/* Итог                                                               */
/* ------------------------------------------------------------------ */

echo "Поиск по сайту: самопроверка\n\n";
echo 'Документов в индексе: ' . count($documents) . "\n";
echo 'Запросов в наборе: ' . count($cases) . "\n\n";

foreach ($checks as $message) {
    echo '  + ' . $message . "\n";
}

foreach ($failures as $message) {
    echo '  x ' . $message . "\n";
}

echo "\n";
if ($failures !== []) {
    echo 'Провалено проверок: ' . count($failures) . ".\n";
    exit(1);
}

echo 'Все проверки пройдены (' . count($checks) . ").\n";
exit(0);
