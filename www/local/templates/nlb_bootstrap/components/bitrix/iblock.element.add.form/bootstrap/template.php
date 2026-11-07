<?php
/**
 * Шаблон bitrix:iblock.element.add.form → формы заявок сайта.
 *
 * Один шаблон на три формы: тип передаётся в NLB_FORM (request, booking,
 * order). Поля и разметку рисует nlb_form_render() из
 * local/php_interface/include/nlb_forms.php, чтобы модалка в подвале и форма
 * на странице отправляли одинаковые имена полей.
 *
 * Запись создаёт сам компонент (CIBlockElement::Add). Проверка занятого
 * слота и письмо-уведомление — обработчики событий инфоблока в nlb_forms.php.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);

$type = (string)($arParams['NLB_FORM'] ?? '');
if ($type === '' || !function_exists('nlb_form_render') || !function_exists('nlb_forms_config')) {
    echo '<div class="alert alert-warning mb-0" role="alert">Форма не настроена: проверьте NLB_FORM.</div>';
    return;
}

$config = nlb_forms_config();
if (!isset($config[$type])) {
    echo '<div class="alert alert-warning mb-0" role="alert">Неизвестный тип формы.</div>';
    return;
}

$errors = [];
if (!empty($arResult['ERRORS'])) {
    foreach ((array)$arResult['ERRORS'] as $error) {
        if (is_array($error)) {
            $error = implode(' ', array_map('strval', $error));
        }
        $error = trim((string)$error);
        if ($error !== '') {
            $errors[] = $error;
        }
    }
}

if (!$errors && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && is_object($APPLICATION)) {
    $exception = $APPLICATION->GetException();
    if ($exception) {
        $errors[] = (string)$exception->GetString();
    }
}

$isSent = isset($_GET['sent']) && (string)$_GET['sent'] === '1';
$addedId = (int)($arResult['ELEMENT_ID'] ?? 0);
$message = trim((string)($arResult['MESSAGE'] ?? ''));
$success = '';
if ($isSent) {
    $success = nlb_form_success_text($type);
} elseif ($addedId > 0) {
    $success = nlb_form_success_text($type);
} elseif ($message !== '' && !$errors) {
    $success = $message;
}

$values = isset($arParams['NLB_VALUES']) && is_array($arParams['NLB_VALUES'])
    ? $arParams['NLB_VALUES']
    : [];
$anchor = (string)($config[$type]['anchor'] ?? '');
$resetUrl = is_object($APPLICATION)
    ? $APPLICATION->GetCurPageParam('', ['sent'])
    : (string)($_SERVER['REQUEST_URI'] ?? '/');
if ($anchor !== '') {
    $resetUrl .= '#' . $anchor;
}
?>

<?php if ($success !== ''): ?>
    <div class="form-success" role="status">
        <span class="form-success-icon" aria-hidden="true">
            <svg class="i i-24"><use href="#i-check"/></svg>
        </span>
        <p class="form-success-text"><?= htmlspecialcharsbx($success) ?></p>
        <a class="btn btn-nlb-outline btn-sm mt-2" href="<?= htmlspecialcharsbx($resetUrl) ?>">
            Отправить ещё одну заявку
        </a>
    </div>
<?php else: ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 ps-3">
                <?php foreach (array_unique($errors) as $error): ?>
                    <li><?= htmlspecialcharsbx($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php
    nlb_form_render($type, [
        'values' => $values,
        'captcha' => (string)($arParams['USE_CAPTCHA'] ?? 'N') === 'Y',
        'form_id' => 'nlb-' . $type . '-form',
    ]);
    ?>
<?php endif; ?>
