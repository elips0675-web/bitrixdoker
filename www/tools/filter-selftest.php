<?php
/**
 * Самопроверка серверного фильтра чата на общем наборе фраз.
 *
 * Запуск: php tools/filter-selftest.php
 *         php tools/filter-selftest.php --json
 *
 * Что проверяется:
 *  - каждая фраза из db/tests/fixtures/filter-cases.json даёт ожидаемый вердикт;
 *  - тематические корни отделены от мата: «ненавиж» лежит в sensitive,
 *    а не в blocked, поэтому «книги о ненависти» проходят;
 *  - в наборе есть фразы всех четырёх вердиктов: '', rude, spam, gibberish.
 *
 * Режим --json печатает списки и результаты для tools/check-filter-parity.mjs:
 * тот же набор прогоняется через браузерный фильтр и сверяется с серверным.
 *
 * Выход: 0 — все проверки прошли, 1 — есть падения.
 */

declare(strict_types=1);

define('B_PROLOG_INCLUDED', true);

$package = dirname(__DIR__);

require $package . '/local/php_interface/include/nlb_text_filter.php';

$jsonMode = in_array('--json', $argv ?? [], true);
$fixturePath = $package . '/db/tests/fixtures/filter-cases.json';

if (!is_file($fixturePath)) {
    fwrite(STDERR, "Нет набора фраз: db/tests/fixtures/filter-cases.json\n");
    exit(1);
}

$fixture = json_decode((string)file_get_contents($fixturePath), true);
if (!is_array($fixture) || !isset($fixture['cases']) || !is_array($fixture['cases'])) {
    fwrite(STDERR, "Набор фраз повреждён: нет массива cases.\n");
    exit(1);
}

$cases = $fixture['cases'];
$failures = [];
$results = [];
$counts = array_fill_keys(['', 'rude', 'spam', 'gibberish'], 0);

foreach ($cases as $index => $case) {
    $text = is_array($case) ? (string)($case['text'] ?? '') : '';
    $expected = is_array($case) ? (string)($case['verdict'] ?? '') : '';
    $actual = nlb_text_problem($text);

    $results[] = [
        'text' => $text,
        'expected' => $expected,
        'actual' => $actual,
    ];

    if (isset($counts[$expected])) {
        $counts[$expected]++;
    }

    if ($actual !== $expected) {
        $failures[] = [
            'index' => $index,
            'text' => $text,
            'expected' => $expected,
            'actual' => $actual,
        ];
    }
}

$patternBody = static function (string $pattern): string {
    if ($pattern === '' || $pattern[0] !== '/') {
        return $pattern;
    }
    $end = strrpos($pattern, '/');
    if ($end === false || $end === 0) {
        return $pattern;
    }
    return substr($pattern, 1, $end - 1);
};

$lists = [
    'blocked' => nlb_filter_blocked(),
    'sensitive' => nlb_filter_sensitive(),
    'spam' => nlb_filter_spam(),
    'mash' => nlb_filter_mash(),
    'study' => $patternBody(nlb_filter_study()),
    'neutral' => $patternBody(nlb_filter_neutral()),
    'link' => $patternBody(nlb_filter_link()),
];

$checks = [];

if (in_array('ненавиж', $lists['sensitive'], true)) {
    $checks[] = 'тематический корень «ненавиж» в sensitive';
} else {
    $failures[] = [
        'index' => -1,
        'text' => 'ненавиж',
        'expected' => 'sensitive',
        'actual' => 'нет в списке',
    ];
}

if (in_array('ненавиж', $lists['blocked'], true)) {
    $failures[] = [
        'index' => -2,
        'text' => 'ненавиж',
        'expected' => 'не в blocked',
        'actual' => 'лежит в blocked — книжный контекст не спасёт',
    ];
} else {
    $checks[] = '«ненавиж» не лежит в blocked';
}

foreach (['террор', 'насил', 'оружи', 'нарко'] as $root) {
    if (in_array($root, $lists['sensitive'], true)) {
        $checks[] = "тематический корень «{$root}» в sensitive";
        continue;
    }
    $failures[] = [
        'index' => -3,
        'text' => $root,
        'expected' => 'sensitive',
        'actual' => 'нет в списке',
    ];
}

foreach (array_keys($counts) as $verdict) {
    if ($counts[$verdict] > 0) {
        $checks[] = "в наборе есть вердикт «{$verdict}» ({$counts[$verdict]})";
        continue;
    }
    $failures[] = [
        'index' => -4,
        'text' => $verdict,
        'expected' => 'хотя бы одна фраза',
        'actual' => 'ни одной',
    ];
}

if ($jsonMode) {
    echo json_encode([
        'ok' => $failures === [],
        'lists' => $lists,
        'cases' => $results,
        'checks' => $checks,
        'failures' => $failures,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "\n";
    exit($failures === [] ? 0 : 1);
}

echo "Фильтр чата: самопроверка\n\n";
echo "Фраз в наборе: " . count($cases) . "\n";

foreach ($checks as $message) {
    echo "  + {$message}\n";
}

foreach ($failures as $failure) {
    $text = $failure['text'] === '' ? '(пустая строка)' : $failure['text'];
    echo "  x «{$text}»: ожидалось «{$failure['expected']}», получено «{$failure['actual']}»\n";
}

echo "\n";
if ($failures !== []) {
    echo 'Провалено проверок: ' . count($failures) . ".\n";
    exit(1);
}

echo 'Все проверки пройдены (' . (count($cases) + count($checks)) . ").\n";
exit(0);
