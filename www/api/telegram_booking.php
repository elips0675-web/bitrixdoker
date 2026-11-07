<?php
// 1. Подключаем ядро Битрикса (без вывода HTML-шапки)
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

header('Content-Type: application/json; charset=utf-8');

// 2. Простая защита от спама (задайте свой сложный токен)
$secret = $_POST['secret'] ?? '';
if ($secret !== 'YOUR_SUPER_SECRET_TOKEN_123') {
    echo json_encode(["status" => "error", "message" => "Неверный секретный ключ"]);
    exit;
}

// 3. Включаем модуль инфоблоков
if (!CModule::IncludeModule('iblock')) {
    echo json_encode(["status" => "error", "message" => "Модуль инфоблоков не подключен"]);
    exit;
}

// 4. ХАК ДЛЯ ПРАВ ДОСТУПА: 
// Временно авторизуемся под админом (ID=1), чтобы скрипт точно имел право на запись.
// В идеале создайте в Битриксе отдельного пользователя "API Bot" и укажите его ID вместо 1.
global $USER;
if (!$USER->IsAuthorized()) {
    $USER->Authorize(1); 
}

// 5. НАСТРОЙКИ (ЗАМЕНИТЕ НА ВАШИ РЕАЛЬНЫЕ ДАННЫЕ ИЗ АДМИНКИ!)
$IBLOCK_ID = 5; // ID инфоблока "Заявки" / "Bookings"
$PROP_CODE_NAME = "USER_NAME";   // Символьный код свойства "Имя"
$PROP_CODE_PHONE = "PHONE";      // Символьный код свойства "Телефон"
$PROP_CODE_EVENT = "EVENT";      // Символьный код свойства "Событие"

// 6. Формируем массив данных
$arLoadProductArray = [
    "IBLOCK_ID"       => $IBLOCK_ID,
    "NAME"            => "Запись из Telegram: " . ($_POST['event'] ?? 'Не указано'),
    "ACTIVE"          => "Y",
    "PROPERTY_VALUES" => [
        $PROP_CODE_NAME => $_POST['name'] ?? 'Аноним',
        $PROP_CODE_PHONE => $_POST['phone'] ?? 'Не указан',
        $PROP_CODE_EVENT => $_POST['event'] ?? 'Не указано',
    ]
];

// 7. Пытаемся добавить элемент
$el = new CIBlockElement;
$NEW_ID = $el->Add($arLoadProductArray);

if ($NEW_ID) {
    echo json_encode([
        "status" => "success", 
        "booking_id" => $NEW_ID,
        "message" => "Заявка успешно создана"
    ]);
} else {
    // Если ошибка, мы сразу увидим причину (например, "Не заполнено обязательное поле")
    echo json_encode([
        "status" => "error", 
        "message" => "Ошибка Битрикса: " . $el->LAST_ERROR
    ]);
}

// 8. Завершаем скрипт, чтобы не было лишнего вывода
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
?>