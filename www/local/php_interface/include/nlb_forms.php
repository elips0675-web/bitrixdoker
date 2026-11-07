<?php
/**
 * NLB Bootstrap — формы заявок на штатных инфоблоках Битрикса.
 *
 * Три формы пишут элементы в инфоблоки из db/config/iblocks.php:
 *   request  → requests  (обратная связь: страница /feedback/ и модалка в подвале);
 *   booking  → bookings  (бронь услуги на детальной странице услуги);
 *   order    → orders    (заказ сувенира на детальной странице товара).
 *
 * Разметку рисует nlb_form_render(), её же использует шаблон компонента
 * bitrix:iblock.element.add.form, поэтому имена полей совпадают и форма
 * в подвале обрабатывается тем же компонентом на /feedback/.
 *
 * Почтовое уведомление — обработчик OnAfterIBlockElementAdd: письмо не
 * заменяет запись, а только сообщает о ней. Проверка занятого слота —
 * OnBeforeIBlockElementAdd (серверная, независимо от JavaScript).
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!function_exists('nlb_site_id')) {
    function nlb_site_id(): string
    {
        $siteId = defined('SITE_ID') ? (string)SITE_ID : '';
        return $siteId !== '' ? $siteId : 's1';
    }
}

if (!function_exists('nlb_forms_config')) {
    /**
     * Описание форм: инфоблок, поля, тексты.
     *
     * Типы полей: text, email, tel, textarea, date, time, number, hidden.
     * hidden-поле с PROPERTY_TYPE=L получает значение из default_xml.
     *
     * @return array<string,array>
     */
    function nlb_forms_config(): array
    {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        $config = [
            'request' => [
                'iblock' => 'requests',
                'title' => 'Обратная связь',
                'name_mode' => 'field',
                'name_label' => 'Имя',
                'name_default' => 'Обращение с сайта',
                'success' => 'Спасибо! Обращение сохранено. Мы ответим в течение рабочего дня.',
                'submit' => 'Отправить',
                'anchor' => 'form',
                'hint' => '',
                'fields' => [
                    'EMAIL' => [
                        'label' => 'E-mail',
                        'type' => 'email',
                        'required' => true,
                        'col' => '12',
                        'autocomplete' => 'email',
                        'placeholder' => 'you@example.by',
                    ],
                    'PHONE' => [
                        'label' => 'Телефон',
                        'type' => 'tel',
                        'col' => '12',
                        'autocomplete' => 'tel',
                        'placeholder' => '+375 (__) ___-__-__',
                    ],
                    'SUBJECT' => [
                        'label' => 'Тема',
                        'type' => 'text',
                        'col' => '12',
                        'placeholder' => 'Вопрос, предложение, отзыв',
                    ],
                    'MESSAGE' => [
                        'label' => 'Сообщение',
                        'type' => 'textarea',
                        'required' => true,
                        'col' => '12',
                        'placeholder' => 'Напишите, чем помочь…',
                    ],
                    'PAGE' => [
                        'label' => 'Страница-источник',
                        'type' => 'hidden',
                    ],
                    'STATUS' => [
                        'label' => 'Статус',
                        'type' => 'hidden',
                        'default_xml' => 'new',
                    ],
                ],
            ],

            'booking' => [
                'iblock' => 'bookings',
                'title' => 'Забронировать',
                'name_mode' => 'hidden',
                'name_default' => 'Заявка на услугу',
                'success' => 'Спасибо! Бронь сохранена. Подтверждение отправили на e-mail, если вы его указали. Мы свяжемся с вами в течение часа.',
                'submit' => 'Отправить заявку',
                'anchor' => 'booking',
                'hint' => 'Подтверждение записи придёт на e-mail, если вы его укажете.',
                'fields' => [
                    'SERVICE' => [
                        'label' => 'Услуга',
                        'type' => 'hidden',
                    ],
                    'SERVICE_CODE' => [
                        'label' => 'Код услуги',
                        'type' => 'hidden',
                    ],
                    'TARIFF' => [
                        'label' => 'Тариф',
                        'type' => 'hidden',
                    ],
                    'PRICE' => [
                        'label' => 'Стоимость',
                        'type' => 'hidden',
                    ],
                    'DATE' => [
                        'label' => 'Дата',
                        'type' => 'date',
                        'required' => true,
                        'col' => 'sm-6',
                    ],
                    'TIME' => [
                        'label' => 'Время',
                        'type' => 'time',
                        'required' => true,
                        'col' => 'sm-6',
                    ],
                    'PERSON' => [
                        'label' => 'Имя',
                        'type' => 'text',
                        'required' => true,
                        'col' => 'md-6',
                        'autocomplete' => 'name',
                        'placeholder' => 'Ваше имя',
                    ],
                    'PHONE' => [
                        'label' => 'Телефон',
                        'type' => 'tel',
                        'required' => true,
                        'col' => 'md-6',
                        'autocomplete' => 'tel',
                        'placeholder' => '+375 (__) ___-__-__',
                    ],
                    'EMAIL' => [
                        'label' => 'E-mail',
                        'type' => 'email',
                        'col' => '12',
                        'autocomplete' => 'email',
                        'placeholder' => 'you@example.by',
                    ],
                    'COMMENT' => [
                        'label' => 'Комментарий',
                        'type' => 'textarea',
                        'col' => '12',
                        'placeholder' => 'Дополнительная информация или пожелания…',
                    ],
                    'STATUS' => [
                        'label' => 'Статус',
                        'type' => 'hidden',
                        'default_xml' => 'new',
                    ],
                ],
            ],

            'order' => [
                'iblock' => 'orders',
                'title' => 'Заказать сувенир',
                'name_mode' => 'hidden',
                'name_default' => 'Заказ сувенира',
                'success' => 'Спасибо! Заказ сохранён. Мы свяжемся с вами, чтобы подтвердить наличие и оплату.',
                'submit' => 'Оформить заказ',
                'anchor' => 'order',
                'hint' => 'Мы свяжемся с вами, чтобы подтвердить наличие и оплату.',
                'fields' => [
                    'ITEM' => [
                        'label' => 'Товар',
                        'type' => 'hidden',
                    ],
                    'ARTICLE' => [
                        'label' => 'Артикул',
                        'type' => 'hidden',
                    ],
                    'PRICE' => [
                        'label' => 'Цена',
                        'type' => 'hidden',
                    ],
                    'QTY' => [
                        'label' => 'Количество',
                        'type' => 'number',
                        'required' => true,
                        'col' => 'sm-6',
                        'min' => 1,
                        'step' => 1,
                        'default' => 1,
                    ],
                    'PERSON' => [
                        'label' => 'Имя',
                        'type' => 'text',
                        'required' => true,
                        'col' => 'sm-6',
                        'autocomplete' => 'name',
                        'placeholder' => 'Ваше имя',
                    ],
                    'PHONE' => [
                        'label' => 'Телефон',
                        'type' => 'tel',
                        'required' => true,
                        'col' => 'md-6',
                        'autocomplete' => 'tel',
                        'placeholder' => '+375 (__) ___-__-__',
                    ],
                    'EMAIL' => [
                        'label' => 'E-mail',
                        'type' => 'email',
                        'col' => 'md-6',
                        'autocomplete' => 'email',
                        'placeholder' => 'you@example.by',
                    ],
                    'COMMENT' => [
                        'label' => 'Комментарий',
                        'type' => 'textarea',
                        'col' => '12',
                        'placeholder' => 'Размер, цвет, сроки…',
                    ],
                    'STATUS' => [
                        'label' => 'Статус',
                        'type' => 'hidden',
                        'default_xml' => 'new',
                    ],
                ],
            ],
        ];

        return $config;
    }
}

if (!function_exists('nlb_forms_type_by_iblock')) {
    /**
     * Тип формы по ID инфоблока; пустая строка — не наша заявка.
     */
    function nlb_forms_type_by_iblock(int $iblockId): string
    {
        static $map = null;
        if ($map === null) {
            $map = [];
            foreach (nlb_forms_config() as $type => $form) {
                $id = nlb_iblock_id($form['iblock']);
                if ($id > 0) {
                    $map[$id] = $type;
                }
            }
        }
        return $map[$iblockId] ?? '';
    }
}

if (!function_exists('nlb_forms_type_by_name')) {
    /**
     * Тип формы по символьному коду (request, booking, order).
     */
    function nlb_forms_type_by_name(string $name): string
    {
        $config = nlb_forms_config();
        return isset($config[$name]) ? $name : '';
    }
}

if (!function_exists('nlb_form_property_codes')) {
    /**
     * ID свойств для параметра PROPERTY_CODES компонента.
     *
     * Компонент bitrix:iblock.element.add.form ждёт в PROPERTY_CODES и ID
     * свойств, и коды полей элемента. NAME передаём всегда: без него
     * элемент сохранится без названия. Остальные коды из конфига переводим
     * в ID через инфоблок.
     *
     * @return array<int,string>
     */
    function nlb_form_property_codes(string $type): array
    {
        $config = nlb_forms_config();
        if (!isset($config[$type])) {
            return [];
        }

        $properties = nlb_iblock_properties($config[$type]['iblock']);
        $ids = ['NAME'];
        foreach (array_keys($config[$type]['fields']) as $code) {
            $id = (int)($properties[$code]['ID'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids;
    }
}

if (!function_exists('nlb_form_required_codes')) {
    /**
     * ID обязательных свойств для PROPERTY_CODES_REQUIRED.
     *
     * @return array<int,string>
     */
    function nlb_form_required_codes(string $type): array
    {
        $config = nlb_forms_config();
        if (!isset($config[$type])) {
            return [];
        }

        $properties = nlb_iblock_properties($config[$type]['iblock']);
        $ids = ['NAME'];
        foreach ($config[$type]['fields'] as $code => $field) {
            if (empty($field['required'])) {
                continue;
            }
            $id = (int)($properties[$code]['ID'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids;
    }
}

if (!function_exists('nlb_form_success_text')) {
    function nlb_form_success_text(string $type): string
    {
        $config = nlb_forms_config();
        return (string)($config[$type]['success'] ?? 'Спасибо! Заявка сохранена.');
    }
}

if (!function_exists('nlb_form_success_url')) {
    /**
     * URL возврата после успешной отправки: текущая страница + sent=1.
     */
    function nlb_form_success_url(string $type, string $anchor = ''): string
    {
        $config = nlb_forms_config();
        if ($anchor === '') {
            $anchor = (string)($config[$type]['anchor'] ?? '');
        }

        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $hash = '';
        $position = strpos($uri, '#');
        if ($position !== false) {
            $hash = substr($uri, $position);
            $uri = substr($uri, 0, $position);
        }

        $separator = strpos($uri, '?') === false ? '?' : '&';
        $result = $uri . $separator . 'sent=1';
        if ($anchor !== '') {
            $result .= '#' . $anchor;
        } elseif ($hash !== '') {
            $result .= $hash;
        }

        return $result;
    }
}

if (!function_exists('nlb_form_time_options')) {
    /**
     * Часы, которые можно выбрать в брони: 10:00–21:00.
     *
     * @return string[]
     */
    function nlb_form_time_options(): array
    {
        $options = [];
        for ($hour = 10; $hour <= 21; $hour++) {
            $options[] = sprintf('%02d:00', $hour);
        }
        return $options;
    }
}

if (!function_exists('nlb_forms_element_props')) {
    /**
     * Свойства элемента по ID: значения и XML_ID списков.
     *
     * @return array{values:array<string,string>,xml:array<string,string>}
     */
    function nlb_forms_element_props(int $iblockId, int $elementId): array
    {
        $values = [];
        $xml = [];
        $res = CIBlockElement::GetProperty($iblockId, $elementId, ['sort' => 'asc'], []);
        while ($row = $res->Fetch()) {
            $code = (string)($row['CODE'] ?? '');
            if ($code === '') {
                continue;
            }
            $value = $row['VALUE'] ?? '';
            if (is_array($value)) {
                $value = implode(', ', array_filter($value, static function ($one) {
                    return is_scalar($one) && (string)$one !== '';
                }));
            }
            $values[$code] = trim((string)$value);

            /* VALUE_XML_ID приходит не во всех версиях ядра: для списков
               подстраховываемся поиском по ID значения. */
            $xmlValue = (string)($row['VALUE_XML_ID'] ?? '');
            if ($xmlValue === '' && !empty($row['VALUE_ENUM_ID'])) {
                $propertyId = (int)($row['IBLOCK_PROPERTY_ID'] ?? 0);
                foreach (nlb_property_enums($propertyId) as $enum) {
                    if ((int)$enum['ID'] === (int)$row['VALUE_ENUM_ID']) {
                        $xmlValue = $enum['XML_ID'];
                        break;
                    }
                }
            }
            if ($xmlValue !== '') {
                $xml[$code] = $xmlValue;
            }
        }
        return ['values' => $values, 'xml' => $xml];
    }
}

if (!function_exists('nlb_form_slots')) {
    /**
     * Занятые слоты услуги: [дата => [время, …]].
     *
     * Учитываются только активные заявки; отменённые (STATUS=cancelled)
     * слот не занимают.
     *
     * @return array<string,string[]>
     */
    function nlb_form_slots(string $serviceCode): array
    {
        if ($serviceCode === '') {
            return [];
        }

        $iblockId = nlb_iblock_id('bookings');
        if ($iblockId <= 0) {
            return [];
        }

        $result = [];
        $today = date('Y-m-d');
        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'PROPERTY_SERVICE_CODE' => $serviceCode,
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            ['ID']
        );
        while ($row = $res->Fetch()) {
            $props = nlb_forms_element_props($iblockId, (int)$row['ID']);
            $date = (string)($props['values']['DATE'] ?? '');
            $time = (string)($props['values']['TIME'] ?? '');
            $status = (string)($props['xml']['STATUS'] ?? '');
            if ($date === '' || $time === '' || $date < $today || $status === 'cancelled') {
                continue;
            }
            $result[$date][] = $time;
        }

        foreach ($result as $date => $times) {
            $times = array_values(array_unique($times));
            sort($times);
            $result[$date] = $times;
        }

        return $result;
    }
}

if (!function_exists('nlb_form_slot_taken')) {
    /**
     * Занят ли конкретный слот (кроме отменённых заявок).
     */
    function nlb_form_slot_taken(string $serviceCode, string $date, string $time): bool
    {
        $iblockId = nlb_iblock_id('bookings');
        if ($iblockId <= 0 || $serviceCode === '' || $date === '' || $time === '') {
            return false;
        }

        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'PROPERTY_SERVICE_CODE' => $serviceCode,
                'PROPERTY_DATE' => $date,
                'PROPERTY_TIME' => $time,
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            ['ID']
        );
        while ($row = $res->Fetch()) {
            $props = nlb_forms_element_props($iblockId, (int)$row['ID']);
            if ((string)($props['xml']['STATUS'] ?? '') !== 'cancelled') {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('nlb_forms_posted_value')) {
    /**
     * Значение поля после неудачной отправки: из $_POST, затем из $values.
     */
    function nlb_forms_posted_value(int $propertyId, array $values, string $code, string $default = ''): string
    {
        if ($propertyId > 0 && isset($_POST['PROPERTY'][$propertyId])) {
            $posted = $_POST['PROPERTY'][$propertyId];
            if (is_array($posted)) {
                $posted = reset($posted);
            }
            if (is_array($posted)) {
                $posted = $posted['VALUE'] ?? '';
            }
            $posted = trim((string)$posted);
            if ($posted !== '') {
                return $posted;
            }
        }

        if (array_key_exists($code, $values)) {
            return (string)$values[$code];
        }

        return $default;
    }
}

if (!function_exists('nlb_form_element_name')) {
    /**
     * Название элемента заявки: его видно в списке инфоблока в админке.
     *
     * Бронь и заказ собираются из уже известных данных (услуга/товар),
     * чтобы в списке не было одинаковых «Заявка на услугу». Дата и время
     * добавляются, если они пришли в значениях; JavaScript уточняет то же
     * самое на клиенте перед отправкой.
     */
    function nlb_form_element_name(string $type, array $values = []): string
    {
        $config = nlb_forms_config();
        $default = (string)($config[$type]['name_default'] ?? 'Заявка с сайта');

        $pick = static function (string $code) use ($values): string {
            if (!isset($values[$code])) {
                return '';
            }
            $value = $values[$code];
            if (is_array($value)) {
                $value = reset($value);
            }
            return trim((string)$value);
        };

        if ($type === 'booking') {
            $service = $pick('SERVICE');
            $name = $service !== '' ? 'Бронь: ' . $service : $default;
            $date = $pick('DATE');
            $time = $pick('TIME');
            if ($date !== '') {
                $name .= ' — ' . $date;
            }
            if ($time !== '') {
                $name .= ' ' . $time;
            }
            return $name;
        }

        if ($type === 'order') {
            $item = $pick('ITEM');
            return $item !== '' ? 'Заказ: ' . $item : $default;
        }

        return $default;
    }
}

if (!function_exists('nlb_form_render')) {
    /**
     * Разметка формы заявки.
     *
     * @param array{
     *   action?:string, values?:array, layout?:string, captcha?:bool,
     *   form_id?:string, submit?:string, show_hint?:bool
     * } $options
     */
    function nlb_form_render(string $type, array $options = []): void
    {
        $config = nlb_forms_config();
        if (!isset($config[$type])) {
            return;
        }
        $form = $config[$type];
        $iblockId = nlb_iblock_id($form['iblock']);
        if ($iblockId <= 0) {
            echo '<div class="alert alert-warning mb-0" role="alert">';
            echo 'Форма временно недоступна: не создан инфоблок <code>'
                . htmlspecialcharsbx($form['iblock']) . '</code>. Запустите миграции базы.';
            echo '</div>';
            return;
        }

        $properties = nlb_iblock_properties($form['iblock']);
        $values = isset($options['values']) && is_array($options['values']) ? $options['values'] : [];
        $layout = (string)($options['layout'] ?? 'page');
        $formId = (string)($options['form_id'] ?? 'nlb-form-' . $type);
        $action = (string)($options['action'] ?? (defined('POST_FORM_ACTION_URI') ? POST_FORM_ACTION_URI : ($_SERVER['REQUEST_URI'] ?? '')));
        $submit = (string)($options['submit'] ?? $form['submit']);
        $showHint = !array_key_exists('show_hint', $options) || (bool)$options['show_hint'];
        $useCaptcha = !empty($options['captcha']);

        $slotsJson = '';
        if ($type === 'booking') {
            $serviceCode = (string)($values['SERVICE_CODE'] ?? '');
            $slots = nlb_form_slots($serviceCode);
            $slotsJson = (string)json_encode($slots, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        }

        $classes = 'feedback-form needs-validation';
        if ($type === 'booking') {
            $classes .= ' booking-form';
        }
        if ($layout === 'modal') {
            $classes .= ' feedback-form--modal';
        }
        ?>
        <form class="<?= $classes ?>" id="<?= htmlspecialcharsbx($formId) ?>"
              action="<?= htmlspecialcharsbx($action) ?>" method="post" novalidate
              data-nlb-form="<?= htmlspecialcharsbx($type) ?>"
              <?= $slotsJson !== '' ? 'data-nlb-slots="' . htmlspecialcharsbx($slotsJson) . '"' : '' ?>>
            <?= bitrix_sessid_post() ?>
            <input type="hidden" name="iblock_submit" value="Y">

            <?php if (($form['name_mode'] ?? 'hidden') === 'field'): ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="<?= htmlspecialcharsbx($formId) ?>-NAME">
                            <?= htmlspecialcharsbx($form['name_label'] ?? 'Имя') ?> *
                        </label>
                        <input class="form-control" id="<?= htmlspecialcharsbx($formId) ?>-NAME"
                               type="text" name="PROPERTY[NAME][0]" data-nlb-field="NAME"
                               value="<?= htmlspecialcharsbx(trim((string)($_POST['PROPERTY']['NAME'][0] ?? ''))) ?>"
                               autocomplete="name" placeholder="Ваше имя" required>
                        <div class="invalid-feedback">Укажите имя.</div>
                    </div>
                    <div class="col-md-6"></div>
                </div>
            <?php else: ?>
                <?php
                $nameValue = nlb_form_element_name($type, $values);
                if (isset($_POST['PROPERTY']['NAME'][0]) && trim((string)$_POST['PROPERTY']['NAME'][0]) !== '') {
                    $nameValue = trim((string)$_POST['PROPERTY']['NAME'][0]);
                }
                ?>
                <input type="hidden" name="PROPERTY[NAME][0]" data-nlb-field="NAME"
                       value="<?= htmlspecialcharsbx($nameValue) ?>">
            <?php endif; ?>

            <div class="row g-3<?= ($form['name_mode'] ?? 'hidden') === 'field' ? ' mt-1' : '' ?>">
                <?php foreach ($form['fields'] as $code => $field): ?>
                    <?php
                    $property = $properties[$code] ?? null;
                    $propertyId = $property ? (int)$property['ID'] : 0;
                    $propertyType = $property ? (string)$property['PROPERTY_TYPE'] : 'S';
                    $fieldType = (string)($field['type'] ?? 'text');
                    $inputId = $formId . '-' . $code;
                    $required = !empty($field['required']);
                    $label = (string)($field['label'] ?? $code);
                    $col = (string)($field['col'] ?? '12');
                    $isList = $propertyType === 'L';
                    $current = '';

                    if ($propertyId <= 0) {
                        continue;
                    }

                    if ($fieldType === 'hidden') {
                        if ($isList) {
                            $defaultXml = (string)($field['default_xml'] ?? '');
                            $enums = nlb_property_enums($propertyId);
                            $enumValue = '';
                            foreach ($enums as $enum) {
                                if ($defaultXml !== '' && $enum['XML_ID'] === $defaultXml) {
                                    $enumValue = (string)$enum['ID'];
                                    break;
                                }
                                if ($enumValue === '') {
                                    $enumValue = (string)$enum['ID'];
                                }
                            }
                            $current = $enumValue;
                        } else {
                            $current = nlb_forms_posted_value($propertyId, $values, $code);
                        }
                        ?>
                        <input type="hidden" name="PROPERTY[<?= $propertyId ?>][0]"
                               data-nlb-field="<?= htmlspecialcharsbx($code) ?>"
                               value="<?= htmlspecialcharsbx($current) ?>">
                        <?php
                        continue;
                    }

                    $current = nlb_forms_posted_value($propertyId, $values, $code, (string)($field['default'] ?? ''));
                    ?>
                    <div class="col-<?= htmlspecialcharsbx($col) ?>">
                        <label class="form-label" for="<?= htmlspecialcharsbx($inputId) ?>">
                            <?= htmlspecialcharsbx($label) ?><?= $required ? ' *' : '' ?>
                        </label>

                        <?php if ($fieldType === 'textarea'): ?>
                            <textarea class="form-control" id="<?= htmlspecialcharsbx($inputId) ?>"
                                      name="PROPERTY[<?= $propertyId ?>][0]" rows="<?= $layout === 'modal' ? '3' : '4' ?>"
                                      data-nlb-field="<?= htmlspecialcharsbx($code) ?>"
                                      placeholder="<?= htmlspecialcharsbx((string)($field['placeholder'] ?? '')) ?>"<?= $required ? ' required' : '' ?>><?= htmlspecialcharsbx($current) ?></textarea>
                        <?php elseif ($fieldType === 'time' && $type === 'booking'): ?>
                            <select class="form-select" id="<?= htmlspecialcharsbx($inputId) ?>"
                                    name="PROPERTY[<?= $propertyId ?>][0]"
                                    data-nlb-field="<?= htmlspecialcharsbx($code) ?>"<?= $required ? ' required' : '' ?>>
                                <option value="">Выберите время</option>
                                <?php foreach (nlb_form_time_options() as $time): ?>
                                    <option value="<?= htmlspecialcharsbx($time) ?>"<?= $current === $time ? ' selected' : '' ?>>
                                        <?= htmlspecialcharsbx($time) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text" data-nlb-slots-hint></div>
                        <?php elseif ($fieldType === 'date'): ?>
                            <input class="form-control" id="<?= htmlspecialcharsbx($inputId) ?>"
                                   type="date" name="PROPERTY[<?= $propertyId ?>][0]"
                                   data-nlb-field="<?= htmlspecialcharsbx($code) ?>"
                                   min="<?= date('Y-m-d') ?>" value="<?= htmlspecialcharsbx($current) ?>"<?= $required ? ' required' : '' ?>>
                        <?php elseif ($fieldType === 'number'): ?>
                            <input class="form-control" id="<?= htmlspecialcharsbx($inputId) ?>"
                                   type="number" name="PROPERTY[<?= $propertyId ?>][0]"
                                   data-nlb-field="<?= htmlspecialcharsbx($code) ?>"
                                   min="<?= (int)($field['min'] ?? 1) ?>" step="<?= (int)($field['step'] ?? 1) ?>"
                                   value="<?= htmlspecialcharsbx($current) ?>"<?= $required ? ' required' : '' ?>>
                        <?php elseif ($fieldType === 'email' || $fieldType === 'tel'): ?>
                            <input class="form-control" id="<?= htmlspecialcharsbx($inputId) ?>"
                                   type="<?= $fieldType === 'email' ? 'email' : 'tel' ?>"
                                   name="PROPERTY[<?= $propertyId ?>][0]"
                                   data-nlb-field="<?= htmlspecialcharsbx($code) ?>"
                                   value="<?= htmlspecialcharsbx($current) ?>"
                                   autocomplete="<?= htmlspecialcharsbx((string)($field['autocomplete'] ?? 'on')) ?>"
                                   placeholder="<?= htmlspecialcharsbx((string)($field['placeholder'] ?? '')) ?>"<?= $required ? ' required' : '' ?>>
                        <?php else: ?>
                            <input class="form-control" id="<?= htmlspecialcharsbx($inputId) ?>"
                                   type="text" name="PROPERTY[<?= $propertyId ?>][0]"
                                   data-nlb-field="<?= htmlspecialcharsbx($code) ?>"
                                   value="<?= htmlspecialcharsbx($current) ?>"
                                   autocomplete="<?= htmlspecialcharsbx((string)($field['autocomplete'] ?? 'on')) ?>"
                                   placeholder="<?= htmlspecialcharsbx((string)($field['placeholder'] ?? '')) ?>"<?= $required ? ' required' : '' ?>>
                        <?php endif; ?>

                        <div class="invalid-feedback">
                            <?= $required ? 'Заполните поле.' : '' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($useCaptcha): ?>
                <?php
                $captchaCode = '';
                if (isset($GLOBALS['APPLICATION'])
                    && is_object($GLOBALS['APPLICATION'])
                    && method_exists($GLOBALS['APPLICATION'], 'CaptchaGetCode')) {
                    $captchaCode = (string)$GLOBALS['APPLICATION']->CaptchaGetCode();
                }
                if ($captchaCode !== ''):
                    ?>
                    <div class="mt-3">
                        <label class="form-label" for="<?= htmlspecialcharsbx($formId) ?>-captcha">Код с картинки *</label>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <input type="hidden" name="captcha_sid" value="<?= htmlspecialcharsbx($captchaCode) ?>">
                            <img src="/bitrix/tools/captcha.php?captcha_sid=<?= urlencode($captchaCode) ?>"
                                 alt="Код проверки" width="180" height="40" loading="lazy" decoding="async">
                            <input class="form-control" id="<?= htmlspecialcharsbx($formId) ?>-captcha"
                                   type="text" name="captcha_word" autocomplete="off" required
                                   style="max-width: 220px">
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="form-check mt-3">
                <input class="form-check-input" id="<?= htmlspecialcharsbx($formId) ?>-consent"
                       type="checkbox" name="CONSENT" value="Y" required>
                <label class="form-check-label small" for="<?= htmlspecialcharsbx($formId) ?>-consent">
                    Согласен на обработку персональных данных
                </label>
                <div class="invalid-feedback">Нужно согласие.</div>
            </div>

            <button class="btn btn-nlb w-100 mt-3 btn-icon" type="submit">
                <?= htmlspecialcharsbx($submit) ?>
                <svg class="i" aria-hidden="true"><use href="#i-send"/></svg>
            </button>

            <?php if ($showHint && (string)($form['hint'] ?? '') !== ''): ?>
                <p class="form-hint mt-3 mb-0 text-center"><?= htmlspecialcharsbx($form['hint']) ?></p>
            <?php endif; ?>
        </form>
        <?php
    }
}

if (!function_exists('nlb_forms_field_values')) {
    /**
     * Значения свойств из $arFields события инфоблока: CODE => строка.
     *
     * @return array<string,string>
     */
    function nlb_forms_field_values(int $iblockId, array $arFields): array
    {
        $type = nlb_forms_type_by_iblock($iblockId);
        if ($type === '') {
            return [];
        }

        $config = nlb_forms_config();
        $properties = nlb_iblock_properties($config[$type]['iblock']);
        $byId = [];
        foreach ($properties as $code => $row) {
            $byId[(int)$row['ID']] = $code;
        }

        $values = [];
        foreach ((array)($arFields['PROPERTY_VALUES'] ?? []) as $propertyId => $propertyValues) {
            $code = $byId[(int)$propertyId] ?? '';
            if ($code === '') {
                continue;
            }
            if (!is_array($propertyValues)) {
                $values[$code] = trim((string)$propertyValues);
                continue;
            }
            $first = reset($propertyValues);
            if (is_array($first)) {
                $first = $first['VALUE'] ?? '';
            }
            if (is_array($first)) {
                $first = implode(', ', array_filter($first, static function ($one) {
                    return is_scalar($one) && (string)$one !== '';
                }));
            }
            $values[$code] = trim((string)$first);
        }

        return $values;
    }
}

if (!function_exists('nlb_forms_before_add')) {
    /**
     * OnBeforeIBlockElementAdd: согласие и проверка занятого слота.
     *
     * Работает на сервере, поэтому занятое время не запишется даже при
     * отключённом JavaScript.
     */
    function nlb_forms_before_add(&$arFields): bool
    {
        global $APPLICATION;

        $iblockId = (int)($arFields['IBLOCK_ID'] ?? 0);
        $type = nlb_forms_type_by_iblock($iblockId);
        if ($type === '') {
            return true;
        }

        if (trim((string)($arFields['CODE'] ?? '')) === '') {
            $arFields['CODE'] = $type . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
        }
        if (trim((string)($arFields['XML_ID'] ?? '')) === '') {
            $arFields['XML_ID'] = $arFields['CODE'];
        }

        $isFormPost = isset($_SERVER['REQUEST_METHOD'])
            && $_SERVER['REQUEST_METHOD'] === 'POST'
            && isset($_POST['iblock_submit']);

        if ($isFormPost && (string)($_POST['CONSENT'] ?? '') !== 'Y') {
            if (is_object($APPLICATION)) {
                $APPLICATION->ThrowException('Подтвердите согласие на обработку персональных данных.');
            }
            return false;
        }

        if ($type !== 'booking') {
            return true;
        }

        $values = nlb_forms_field_values($iblockId, $arFields);
        $serviceCode = (string)($values['SERVICE_CODE'] ?? '');
        $date = (string)($values['DATE'] ?? '');
        $time = (string)($values['TIME'] ?? '');

        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && $date < date('Y-m-d')) {
            if (is_object($APPLICATION)) {
                $APPLICATION->ThrowException('Дата уже прошла. Выберите другой день.');
            }
            return false;
        }

        if ($serviceCode !== '' && $date !== '' && $time !== '' && nlb_form_slot_taken($serviceCode, $date, $time)) {
            if (is_object($APPLICATION)) {
                $APPLICATION->ThrowException('Это время уже занято. Выберите другой слот.');
            }
            return false;
        }

        return true;
    }
}

if (!function_exists('nlb_forms_date_human')) {
    /**
     * Дата заявки в человеческом виде: 2026-11-06 → «06.11.2026 (пятница)».
     *
     * В письмах и push нужна именно такая запись: ISO-формат читается плохо,
     * а день недели помогает не ошибиться при подтверждении брони.
     */
    function nlb_forms_date_human(string $date): string
    {
        $date = trim($date);
        if ($date === '' || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $match)) {
            return $date;
        }

        $stamp = mktime(12, 0, 0, (int)$match[2], (int)$match[3], (int)$match[1]);
        if ($stamp === false) {
            return $date;
        }

        $weekdays = [
            1 => 'понедельник',
            2 => 'вторник',
            3 => 'среда',
            4 => 'четверг',
            5 => 'пятница',
            6 => 'суббота',
            7 => 'воскресенье',
        ];
        $weekday = $weekdays[(int)date('N', $stamp)] ?? '';

        $human = date('d.m.Y', $stamp);

        return $weekday !== '' ? $human . ' (' . $weekday . ')' : $human;
    }
}

if (!function_exists('nlb_forms_event_exists')) {
    /**
     * Есть ли тип почтового события в системе.
     *
     * Нужен для честного отката: если установка обновила файлы, но не
     * прогнала миграцию 012, письмо о брони уйдёт старым NLB_NEW_REQUEST,
     * а не потеряется молча.
     */
    function nlb_forms_event_exists(string $eventName): bool
    {
        if (!class_exists('CEventType')) {
            return true;
        }

        try {
            $result = CEventType::GetList([], [
                'EVENT_NAME' => $eventName,
                'LID' => 'ru',
            ]);
        } catch (Throwable $e) {
            return true;
        }

        return $result && $result->Fetch() ? true : false;
    }
}

if (!function_exists('nlb_forms_after_add')) {
    /**
     * OnAfterIBlockElementAdd: письмо-уведомление о новой заявке.
     *
     * Запись уже создана; если почта не настроена, заявка всё равно остаётся
     * в инфоблоке.
     */
    function nlb_forms_after_add(&$arFields): void
    {
        $iblockId = (int)($arFields['IBLOCK_ID'] ?? 0);
        $type = nlb_forms_type_by_iblock($iblockId);
        if ($type === '' || !class_exists('CEvent')) {
            return;
        }

        $labels = [
            'request' => 'Обращение',
            'booking' => 'Бронь услуги',
            'order' => 'Заказ сувенира',
        ];
        $values = nlb_forms_field_values($iblockId, $arFields);
        $elementId = (int)($arFields['ID'] ?? 0);
        $dateHuman = nlb_forms_date_human((string)($values['DATE'] ?? ''));

        $fields = [
            'TYPE' => (string)($labels[$type] ?? $type),
            'NAME' => (string)($arFields['NAME'] ?? ''),
            'EMAIL' => (string)($values['EMAIL'] ?? ''),
            'PHONE' => (string)($values['PHONE'] ?? ''),
            'PERSON' => (string)($values['PERSON'] ?? ''),
            'SUBJECT' => (string)($values['SUBJECT'] ?? ''),
            'MESSAGE' => (string)($values['MESSAGE'] ?? ''),
            'COMMENT' => (string)($values['COMMENT'] ?? ''),
            'PAGE' => (string)($values['PAGE'] ?? ''),
            'SERVICE' => (string)($values['SERVICE'] ?? ''),
            'SERVICE_CODE' => (string)($values['SERVICE_CODE'] ?? ''),
            'TARIFF' => (string)($values['TARIFF'] ?? ''),
            'DATE' => (string)($values['DATE'] ?? ''),
            'DATE_HUMAN' => $dateHuman,
            'TIME' => (string)($values['TIME'] ?? ''),
            'ITEM' => (string)($values['ITEM'] ?? ''),
            'ARTICLE' => (string)($values['ARTICLE'] ?? ''),
            'PRICE' => (string)($values['PRICE'] ?? ''),
            'QTY' => (string)($values['QTY'] ?? ''),
            'LINK' => $elementId > 0
                ? '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $iblockId
                    . '&ID=' . $elementId . '&type=nlb_content&lang=ru'
                : '',
        ];

        try {
            if ($type === 'booking') {
                if (nlb_forms_event_exists('NLB_NEW_BOOKING')) {
                    CEvent::Send('NLB_NEW_BOOKING', nlb_site_id(), $fields);
                } else {
                    CEvent::Send('NLB_NEW_REQUEST', nlb_site_id(), $fields);
                }

                $email = trim((string)$fields['EMAIL']);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)
                    && nlb_forms_event_exists('NLB_BOOKING_CLIENT')
                ) {
                    CEvent::Send('NLB_BOOKING_CLIENT', nlb_site_id(), $fields);
                }

                if (function_exists('nlb_push_notify_booking')) {
                    $page = (string)$fields['PAGE'];
                    $url = $page !== '' ? $page . '#booking' : '';
                    $pushValues = [
                        'SERVICE' => $fields['SERVICE'],
                        'DATE_HUMAN' => $dateHuman,
                        'TIME' => $fields['TIME'],
                        'PERSON' => $fields['PERSON'],
                        'PHONE' => $fields['PHONE'],
                    ];
                    nlb_push_notify_booking($pushValues, $url, $elementId);
                }

                return;
            }

            CEvent::Send('NLB_NEW_REQUEST', nlb_site_id(), $fields);
        } catch (Throwable $e) {
            /* Почта не должна ломать сохранение заявки. */
        }
    }
}

if (function_exists('AddEventHandler')) {
    AddEventHandler('iblock', 'OnBeforeIBlockElementAdd', 'nlb_forms_before_add');
    AddEventHandler('iblock', 'OnAfterIBlockElementAdd', 'nlb_forms_after_add');
}
