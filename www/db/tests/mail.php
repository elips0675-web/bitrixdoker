<?php
/**
 * Проверка почтовых событий миграций 008 и 012 на заглушках ядра.
 *
 * Зачем тест: CEventType::GetList() принимает фильтр первым аргументом.
 * Пока фильтр передавали вторым, метод возвращал первую строку b_event_type,
 * и шаг 012 «находил» тип от шага 008 — NLB_NEW_BOOKING и NLB_BOOKING_CLIENT
 * на чистой установке не создавались (на стенде их пришлось делать SQL).
 *
 * Заглушки повторяют настоящую сигнатуру, поэтому ошибка возвращается,
 * а не маскируется. Запускается из db/tests/run.php в песочнице с NLB_ROOT.
 *
 * Запуск: php db/tests/mail.php
 * Выход:  0 — типы и шаблоны создаются по фильтру, 1 — расхождение.
 */

declare(strict_types=1);

require __DIR__ . '/../lib/boot.php';

if (class_exists('CEventType', false) || class_exists('CEventMessage', false)) {
    fwrite(STDERR, "mail.php запускается только на заглушках ядра\n");
    exit(2);
}

final class NlbMailStore
{
    public static array $types = [];
    public static array $messages = [];
    public static array $filters = [];
}

if (!class_exists('CEventType', false)) {
    class CEventType
    {
        public string $LAST_ERROR = '';

        public static function GetList(array $filter = [], array $order = []): NlbStubResult
        {
            NlbMailStore::$filters[] = $filter;
            $rows = [];
            foreach (NlbMailStore::$types as $type) {
                if ((string)($filter['EVENT_NAME'] ?? '') !== '' && $filter['EVENT_NAME'] !== $type['EVENT_NAME']) {
                    continue;
                }
                if ((string)($filter['LID'] ?? '') !== '' && $filter['LID'] !== $type['LID']) {
                    continue;
                }
                $rows[] = $type;
            }
            return new NlbStubResult($rows);
        }

        public function Add(array $fields)
        {
            $eventName = (string)($fields['EVENT_NAME'] ?? '');
            $lid = (string)($fields['LID'] ?? '');
            if ($eventName === '' || $lid === '') {
                $this->LAST_ERROR = 'пустое EVENT_NAME или LID';
                return false;
            }
            foreach (NlbMailStore::$types as $type) {
                if ($type['EVENT_NAME'] === $eventName && $type['LID'] === $lid) {
                    $this->LAST_ERROR = 'тип уже есть';
                    return false;
                }
            }
            $id = count(NlbMailStore::$types) + 1;
            NlbMailStore::$types[$id] = [
                'ID' => $id,
                'EVENT_NAME' => $eventName,
                'LID' => $lid,
                'NAME' => (string)($fields['NAME'] ?? ''),
            ];
            return $id;
        }
    }
}

if (!class_exists('CEventMessage', false)) {
    class CEventMessage
    {
        public string $LAST_ERROR = '';

        public static function GetList(string $by = 'id', string $order = 'asc', array $filter = []): NlbStubResult
        {
            $rows = [];
            foreach (NlbMailStore::$messages as $message) {
                if ((string)($filter['EVENT_NAME'] ?? '') !== '' && $filter['EVENT_NAME'] !== $message['EVENT_NAME']) {
                    continue;
                }
                $lid = (string)($filter['LID'] ?? '');
                if ($lid !== '' && !in_array($lid, $message['LID'], true)) {
                    continue;
                }
                $rows[] = $message;
            }
            return new NlbStubResult($rows);
        }

        public function Add(array $fields)
        {
            $eventName = (string)($fields['EVENT_NAME'] ?? '');
            if ($eventName === '') {
                $this->LAST_ERROR = 'пустое EVENT_NAME';
                return false;
            }
            $id = count(NlbMailStore::$messages) + 1;
            NlbMailStore::$messages[$id] = [
                'ID' => $id,
                'EVENT_NAME' => $eventName,
                'LID' => is_array($fields['LID'] ?? null) ? $fields['LID'] : [(string)($fields['LID'] ?? '')],
                'SUBJECT' => (string)($fields['SUBJECT'] ?? ''),
            ];
            return $id;
        }
    }
}

$requestMail = require nlb_root() . '/db/migrations/008_requests.php';
$bookingMail = require nlb_root() . '/db/migrations/012_booking_mail.php';

$errors = [];

$requestResult = $requestMail();
$bookingResult = $bookingMail();

if ((int)$requestResult['created'] !== 2) {
    $errors[] = '008: ожидалось 2 созданных объекта (тип + шаблон), получено '
        . (int)$requestResult['created'];
}
if ((int)$bookingResult['created'] !== 4) {
    $errors[] = '012: ожидалось 4 созданных объекта (2 типа + 2 шаблона), получено '
        . (int)$bookingResult['created'];
}

$expectedTypes = ['NLB_NEW_REQUEST', 'NLB_NEW_BOOKING', 'NLB_BOOKING_CLIENT'];
$actualTypes = array_column(NlbMailStore::$types, 'EVENT_NAME');
foreach ($expectedTypes as $eventName) {
    if (!in_array($eventName, $actualTypes, true)) {
        $errors[] = 'не создан тип почтового события ' . $eventName;
    }
}
if (count(NlbMailStore::$types) !== 3) {
    $errors[] = 'типов почтовых событий ' . count(NlbMailStore::$types) . ' вместо 3';
}
if (count(NlbMailStore::$messages) !== 3) {
    $errors[] = 'почтовых шаблонов ' . count(NlbMailStore::$messages) . ' вместо 3';
}

$withFilter = 0;
foreach (NlbMailStore::$filters as $filter) {
    if ((string)($filter['EVENT_NAME'] ?? '') !== '') {
        $withFilter++;
    }
}
if ($withFilter < 3) {
    $errors[] = 'CEventType::GetList получил пустой фильтр: '
        . (count(NlbMailStore::$filters) - $withFilter) . ' вызов(ов) без EVENT_NAME';
}

$requestRepeat = $requestMail();
$bookingRepeat = $bookingMail();
if ((int)$requestRepeat['created'] !== 0 || (int)$bookingRepeat['created'] !== 0) {
    $errors[] = 'повторный запуск создал объекты: 008 — ' . (int)$requestRepeat['created']
        . ', 012 — ' . (int)$bookingRepeat['created'];
}
if (count(NlbMailStore::$types) !== 3 || count(NlbMailStore::$messages) !== 3) {
    $errors[] = 'повторный запуск размножил типы или шаблоны';
}

if ($errors) {
    foreach ($errors as $error) {
        fwrite(STDERR, 'ОШИБКА: ' . $error . "\n");
    }
    exit(1);
}

echo "OK: почтовые события 008/012 создаются по фильтру и не дублируются\n";
exit(0);
