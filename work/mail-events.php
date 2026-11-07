<?php
/**
 * Досоздание типов почтовых событий NLB.
 * Проверка существования — прямым SQL, потому что фильтр CEventType::GetList
 * в этом ядре не применяется (возвращает все строки b_event_type).
 */
$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
$_SERVER['REQUEST_METHOD'] = 'GET';
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
define('BX_CRONTAB', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $DB;

$types = [
    'NLB_NEW_REQUEST' => 'Новая заявка с сайта',
    'NLB_NEW_BOOKING' => 'Новая бронь услуги',
    'NLB_BOOKING_CLIENT' => 'Бронь подтверждена клиенту',
];

foreach ($types as $name => $title) {
    $exists = $DB->Query(
        "SELECT ID FROM b_event_type WHERE EVENT_NAME='" . $DB->ForSql($name) . "' AND LID='ru'"
    )->Fetch();

    if ($exists) {
        echo "EXISTS $name id={$exists['ID']}\n";
        continue;
    }

    $ob = new CEventType();
    $id = (int)$ob->Add([
        'LID' => 'ru',
        'EVENT_NAME' => $name,
        'NAME' => $title,
        'DESCRIPTION' => 'Уведомление сайта NLB.',
        'SORT' => 100,
    ]);
    echo $id > 0
        ? "CREATED $name id=$id\n"
        : "FAILED $name: " . $ob->LAST_ERROR . "\n";
}
