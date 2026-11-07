<?php
/**
 * 012 — письма о бронировании: сотруднику и клиенту.
 *
 * До этого шага все заявки уходили одним письмом NLB_NEW_REQUEST на адрес
 * библиотеки. Бронь — единственная заявка, где клиенту нужно подтверждение
 * записи: он выбрал дату и время и должен видеть их в письме.
 *
 * Создаются два почтовых события:
 *  - NLB_NEW_BOOKING    — сотрудникам, с телефоном и ссылкой на заявку;
 *  - NLB_BOOKING_CLIENT — клиенту, EMAIL_TO = #EMAIL# (подставляется из
 *    поля заявки). Отправляется только при валидном e-mail.
 *
 * Повторный запуск ничего не дублирует.
 */

return static function (): array {
    $siteId = nlb_site_id();
    $created = 0;
    $skipped = 0;
    $notes = [];

    $events = [
        'NLB_NEW_BOOKING' => [
            'name' => 'Новая бронь услуги',
            'description' => 'Заявка на бронирование услуги: дата, время, контакты. Письмо сотрудникам библиотеки.',
            'subject' => 'Новая бронь: #SERVICE# — #DATE_HUMAN# #TIME#',
            'to' => 'info@example.by',
            'body' => "Новая заявка на бронирование.\n\n"
                . "Услуга: #SERVICE# (#SERVICE_CODE#)\n"
                . "Тариф: #TARIFF#\n"
                . "Дата: #DATE_HUMAN#\n"
                . "Время: #TIME#\n\n"
                . "Имя: #PERSON#\n"
                . "Телефон: #PHONE#\n"
                . "E-mail: #EMAIL#\n"
                . "Комментарий: #COMMENT#\n\n"
                . "Страница: #PAGE#\n"
                . "Заявка в админке: #LINK#\n",
        ],
        'NLB_BOOKING_CLIENT' => [
            'name' => 'Подтверждение брони клиенту',
            'description' => 'Письмо клиенту о записи на услугу. Адрес берётся из поля E-mail заявки (#EMAIL#).',
            'subject' => 'Вы записаны: #SERVICE# — #DATE_HUMAN# #TIME#',
            'to' => '#EMAIL#',
            'body' => "Здравствуйте, #PERSON#!\n\n"
                . "Мы получили заявку на бронирование.\n\n"
                . "Услуга: #SERVICE#\n"
                . "Дата: #DATE_HUMAN#\n"
                . "Время: #TIME#\n"
                . "Тариф: #TARIFF#\n\n"
                . "Если планы изменились, ответьте на это письмо или позвоните:\n"
                . "+375 (17) 300-00-00\n\n"
                . "Библиотека 2026, пр. Независимости, 116\n",
        ],
    ];

    if (!class_exists('CEventType') || !class_exists('CEventMessage')) {
        $notes[] = 'почтовые события пропущены: нет классов ядра (заглушки)';
        return ['created' => $created, 'skipped' => $skipped, 'notes' => $notes];
    }

    foreach ($events as $eventName => $definition) {
        $typeExists = false;
        $typeRes = CEventType::GetList([], [
            'EVENT_NAME' => $eventName,
            'LID' => 'ru',
        ]);
        if ($typeRes && $typeRes->Fetch()) {
            $typeExists = true;
        }

        if ($typeExists) {
            $skipped++;
        } else {
            $type = new CEventType();
            $typeId = (int)$type->Add([
                'LID' => 'ru',
                'EVENT_NAME' => $eventName,
                'NAME' => $definition['name'],
                'DESCRIPTION' => $definition['description'],
                'SORT' => 100,
            ]);
            if ($typeId > 0) {
                $created++;
                $notes[] = 'создан тип почтового события ' . $eventName;
            } else {
                $error = isset($type->LAST_ERROR) ? (string)$type->LAST_ERROR : '';
                $notes[] = 'не удалось создать тип события ' . $eventName . ': ' . ($error ?: 'неизвестная ошибка');
            }
        }

        $messageExists = false;
        $messageRes = CEventMessage::GetList('id', 'asc', [
            'EVENT_NAME' => $eventName,
            'LID' => $siteId,
        ]);
        if ($messageRes && $messageRes->Fetch()) {
            $messageExists = true;
        }

        if ($messageExists) {
            $skipped++;
            continue;
        }

        $message = new CEventMessage();
        $messageId = (int)$message->Add([
            'ACTIVE' => 'Y',
            'EVENT_NAME' => $eventName,
            'LID' => [$siteId],
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => $definition['to'],
            'BCC' => '',
            'SUBJECT' => $definition['subject'],
            'BODY_TYPE' => 'text',
            'MESSAGE' => $definition['body'],
        ]);
        if ($messageId > 0) {
            $created++;
            $notes[] = 'создан почтовый шаблон ' . $eventName . ' (ID ' . $messageId . ')';
        } else {
            $error = isset($message->LAST_ERROR) ? (string)$message->LAST_ERROR : '';
            $notes[] = 'не удалось создать почтовый шаблон ' . $eventName . ': ' . ($error ?: 'неизвестная ошибка');
        }
    }

    return ['created' => $created, 'skipped' => $skipped, 'notes' => $notes];
};
