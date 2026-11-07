<?php
/**
 * NLB Bootstrap — серверный фильтр сообщений чата.
 *
 * Повторяет проверку из `script.js` (CHAT_BLOCKED / CHAT_SENSITIVE / CHAT_SPAM /
 * CHAT_MASH, chatHit(), chatGibberish(), chatProblem()): клиентская проверка
 * живёт в браузере и обходится отключённым JS или запросом мимо формы, поэтому
 * тот же вердикт нужен на сервере — до того, как текст попадёт в поиск,
 * в очередь оператору или в лог.
 *
 * Списки и вердикты обязаны совпадать с браузерными: паритет проверяет
 * `node tools/check-filter-parity.mjs` на общем наборе фраз
 * `db/tests/fixtures/filter-cases.json`, а сам фильтр — `php tools/filter-selftest.php`.
 *
 * Корни сравниваются по началу слова: «туп» ловит «тупой» и не ловит
 * «ступеньки», «дурак» ловит «дурака», а нейтральные производные
 * («дурацкий», «идиотский», «дурачество») снимаются до проверки.
 *
 * Подключается из local/ajax/chat.php.
 */

if (!function_exists('nlb_filter_blocked')) {
    function nlb_filter_blocked(): array
    {
        return [
            'хуй', 'хуи', 'хуе', 'хуя', 'хую', 'пизд', 'ебат', 'ебан', 'ебал', 'ебл', 'ебуч', 'бля',
            'сука', 'суки', 'суке', 'суку', 'сукой', 'сучар', 'залуп', 'уеб', 'пидор', 'гандон', 'шлюх', 'мраз', 'дебил',
            'кретин', 'идиот', 'дурак', 'дура', 'чмо', 'урод', 'говн', 'дерьм',
            'жоп', 'сран', 'срак', 'жидок', 'жиды', 'жидов',
            'мудак', 'мудил', 'придур', 'сдохн', 'заткн', 'туп', 'долбое',
            'пошел ты', 'пошла ты',
            'fuck', 'shit', 'bitch', 'cunt', 'asshole', 'dick', 'bastard', 'slut',
            'whore', 'nigger', 'idiot', 'stupid', 'moron', 'loser', 'shut up', 'hate you',
        ];
    }
}

if (!function_exists('nlb_filter_sensitive')) {
    function nlb_filter_sensitive(): array
    {
        return [
            'убий', 'убить', 'убил', 'убью', 'убива', 'насил', 'террор', 'экстрем',
            'взорв', 'взрыв', 'расправ', 'оружи', 'нарко', 'порно', 'секс',
            'проститут', 'ненавиж', 'kill', 'murder', 'terror', 'violen', 'weapon', 'drug', 'porn',
        ];
    }
}

if (!function_exists('nlb_filter_spam')) {
    function nlb_filter_spam(): array
    {
        return [
            'казино', 'бинарн', 'пирамид', 'опцион', 'мошенн', 'спам',
            'криптовалют', 'casino', 'crypto', 'pyramid',
        ];
    }
}

if (!function_exists('nlb_filter_mash')) {
    function nlb_filter_mash(): array
    {
        return [
            'asdf', 'sdfg', 'dfgh', 'fghj', 'ghjk', 'hjkl', 'zxcv', 'xcvb',
            'йцук', 'цуке', 'укен', 'фыва', 'ывап', 'вапр', 'апро', 'олдж', 'ячсм', 'чсми',
        ];
    }
}

if (!function_exists('nlb_filter_study')) {
    function nlb_filter_study(): string
    {
        return '/книг|литератур|роман|повест|учебник|пособ|истор|биограф|энциклопед|автор|издани|почита|найти|фонд|каталог|реферат|курсов|диплом|исследован|стать/';
    }
}

if (!function_exists('nlb_filter_link')) {
    function nlb_filter_link(): string
    {
        return '/(https?:\/\/|www\.|t\.me\/|vk\.com\/)/';
    }
}

if (!function_exists('nlb_filter_neutral')) {
    function nlb_filter_neutral(): string
    {
        return '/дурацк[а-я]*|дурачеств[а-я]*|идиотск[а-я]*/u';
    }
}

if (!function_exists('nlb_filter_hit')) {
    /**
     * Совпадение по началу слова: «туп» → «тупой», но не «ступеньки».
     */
    function nlb_filter_hit(string $value, array $roots): bool
    {
        foreach ($roots as $root) {
            $pattern = '/(?<![\p{L}])' . preg_quote($root, '/') . '/u';
            if (preg_match($pattern, $value) === 1) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('nlb_filter_gibberish')) {
    function nlb_filter_gibberish(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (preg_match('/(.)\1{4,}/u', $value) === 1) {
            return true;
        }

        $letters = (string)preg_replace('/[^a-zа-я]/u', '', $value);
        $length = mb_strlen($letters, 'UTF-8');

        if ($length >= 6) {
            $chars = preg_split('//u', $letters, -1, PREG_SPLIT_NO_EMPTY);
            $unique = is_array($chars) ? count(array_unique($chars)) : 0;
            if ($length > 0 && $unique / $length < 0.35) {
                return true;
            }

            $triples = [];
            for ($i = 0; $i <= $length - 3; $i++) {
                $triple = mb_substr($letters, $i, 3, 'UTF-8');
                $triples[$triple] = ($triples[$triple] ?? 0) + 1;
                if ($triples[$triple] >= 3) {
                    return true;
                }
            }
        }

        $words = preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        foreach (is_array($words) ? $words : [] as $word) {
            $word = (string)preg_replace('/[^a-zа-я]/u', '', $word);
            if (mb_strlen($word, 'UTF-8') < 3) {
                continue;
            }

            if (preg_match('/[aeiouyаеиоуыэюя]/u', $word) !== 1) {
                return true;
            }

            if (preg_match('/[bcdfghjklmnpqrstvwxzбвгджзйклмнпрстфхцчшщ]{6,}/u', $word) === 1) {
                return true;
            }

            foreach (nlb_filter_mash() as $mash) {
                if (mb_strpos($word, $mash, 0, 'UTF-8') !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('nlb_text_problem')) {
    /**
     * Вердикт по тексту сообщения: '' | 'rude' | 'spam' | 'gibberish'.
     */
    function nlb_text_problem(string $text): string
    {
        $value = mb_strtolower($text, 'UTF-8');
        if (!mb_check_encoding($value, 'UTF-8')) {
            return 'gibberish';
        }

        $value = str_replace('ё', 'е', $value);
        $value = (string)preg_replace(nlb_filter_neutral(), ' ', $value);

        $study = preg_match(nlb_filter_study(), $value) === 1;

        if (nlb_filter_hit($value, nlb_filter_blocked()) || nlb_filter_hit($value, nlb_filter_spam())) {
            return 'rude';
        }

        if (!$study && nlb_filter_hit($value, nlb_filter_sensitive())) {
            return 'rude';
        }

        $links = str_replace(
            ['www.nlb.by', 'www.example.by', 'nlb.by', 'example.by'],
            '',
            $value
        );
        if (preg_match(nlb_filter_link(), $links) === 1) {
            return 'spam';
        }

        if (nlb_filter_gibberish($links)) {
            return 'gibberish';
        }

        return '';
    }
}
