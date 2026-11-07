<?php
/**
 * Шаблон bitrix:main.feedback → форма в стилях сайта.
 *
 * Бесплатный компонент из главного модуля: отправляет письмо по шаблону
 * FEEDBACK_FORM и пишет запись в журнал почтовых событий.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);

$errors = isset($arResult['ERRORS']) && is_array($arResult['ERRORS']) ? $arResult['ERRORS'] : [];
$okMessage = (string)($arResult['OK_MESSAGE'] ?? $arResult['MESSAGE'] ?? '');
$paramsHash = (string)($arResult['PARAMS_HASH'] ?? '');
$captchaCode = (string)($arResult['CAPTCHA_CODE'] ?? '');
$useCaptcha = (string)($arParams['USE_CAPTCHA'] ?? 'N') === 'Y';
$isBooking = (string)($arParams['NLB_BOOKING'] ?? 'N') === 'Y';
$bookingService = (string)($arParams['NLB_SERVICE'] ?? '');
$required = isset($arParams['REQUIRED_FIELDS']) && is_array($arParams['REQUIRED_FIELDS'])
    ? $arParams['REQUIRED_FIELDS']
    : ['NAME', 'EMAIL', 'MESSAGE'];
$isRequired = static function (string $field) use ($required): bool {
    return in_array($field, $required, true);
};
?>
<?php if ($errors): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialcharsbx((string)$error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($okMessage !== ''): ?>
    <div class="alert alert-success" role="status"><?= htmlspecialcharsbx($okMessage) ?></div>
<?php endif; ?>

<form class="feedback-form needs-validation<?= $isBooking ? ' booking-form' : '' ?>"
      action="<?= POST_FORM_ACTION_URI ?>" method="post" novalidate
      <?= $isBooking ? 'data-booking="Y"' : '' ?>
      data-service="<?= htmlspecialcharsbx($bookingService) ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="PARAMS_HASH" value="<?= htmlspecialcharsbx($paramsHash) ?>">
    <?php if ($isBooking): ?>
        <input type="hidden" name="NLB_SERVICE" value="<?= htmlspecialcharsbx($bookingService) ?>">
        <input type="hidden" name="NLB_TARIFF" value="">
    <?php endif; ?>

    <div class="row g-3">
        <div class="<?= $isBooking ? 'col-md-6' : 'col-md-6' ?>">
            <label class="form-label" for="nlbFeedbackName">Имя<?= $isRequired('NAME') ? ' *' : '' ?></label>
            <input class="form-control" id="nlbFeedbackName" type="text" name="user_name"
                   value="<?= htmlspecialcharsbx((string)($arResult['AUTHOR_NAME'] ?? '')) ?>"
                   autocomplete="name" placeholder="Ваше имя"<?= $isRequired('NAME') ? ' required' : '' ?>>
            <div class="invalid-feedback">Укажите имя.</div>
        </div>

        <?php if ($isBooking): ?>
            <div class="col-md-6">
                <label class="form-label" for="nlbFeedbackPhone">Телефон *</label>
                <input class="form-control" id="nlbFeedbackPhone" type="tel" name="NLB_PHONE"
                       autocomplete="tel" placeholder="+375 (__) ___-__-__" required>
                <div class="invalid-feedback">Укажите телефон.</div>
            </div>
        <?php endif; ?>

        <div class="<?= $isBooking ? 'col-12' : 'col-md-6' ?>">
            <label class="form-label" for="nlbFeedbackEmail">E-mail<?= $isRequired('EMAIL') ? ' *' : '' ?></label>
            <input class="form-control" id="nlbFeedbackEmail" type="email" name="user_email"
                   value="<?= htmlspecialcharsbx((string)($arResult['AUTHOR_EMAIL'] ?? '')) ?>"
                   autocomplete="email" placeholder="you@example.by"<?= $isRequired('EMAIL') ? ' required' : '' ?>>
            <div class="invalid-feedback">Укажите корректный e-mail.</div>
        </div>

        <?php if ($isBooking): ?>
            <div class="col-sm-6">
                <label class="form-label" for="nlbFeedbackDate">Дата *</label>
                <input class="form-control" id="nlbFeedbackDate" type="date" name="NLB_DATE" required>
                <div class="invalid-feedback">Выберите дату.</div>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="nlbFeedbackTime">Время *</label>
                <input class="form-control" id="nlbFeedbackTime" type="time" name="NLB_TIME" required>
                <div class="invalid-feedback">Выберите время.</div>
            </div>
        <?php endif; ?>

        <?php if ($isBooking): ?>
            <div class="col-12">
                <label class="form-label" for="nlbFeedbackComment">Комментарий</label>
                <textarea class="form-control" id="nlbFeedbackComment" name="NLB_COMMENT" rows="4"
                          placeholder="Дополнительная информация или пожелания…"><?= htmlspecialcharsbx((string)($arResult['MESSAGE_TEXT'] ?? '')) ?></textarea>
                <input type="hidden" name="MESSAGE" value="">
            </div>
            <noscript>
                <div class="col-12">
                    <div class="alert alert-warning mb-0" role="alert">
                        Для отправки заявки включите JavaScript — телефон, дата и время
                        добавляются в сообщение автоматически.
                    </div>
                </div>
            </noscript>
        <?php else: ?>
            <div class="col-12">
                <label class="form-label" for="nlbFeedbackMessage">Сообщение<?= $isRequired('MESSAGE') ? ' *' : '' ?></label>
                <textarea class="form-control" id="nlbFeedbackMessage" name="MESSAGE" rows="4"
                          <?= $isRequired('MESSAGE') ? 'required' : '' ?>><?= htmlspecialcharsbx((string)($arResult['MESSAGE_TEXT'] ?? '')) ?></textarea>
                <div class="invalid-feedback">Напишите сообщение.</div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($useCaptcha): ?>
        <div class="mt-3">
            <label class="form-label" for="nlbFeedbackCaptcha">Код с картинки *</label>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <input type="hidden" name="captcha_sid" value="<?= htmlspecialcharsbx($captchaCode) ?>">
                <img src="/bitrix/tools/captcha.php?captcha_sid=<?= urlencode($captchaCode) ?>"
                     alt="Код проверки" width="180" height="40" loading="lazy" decoding="async">
                <input class="form-control" id="nlbFeedbackCaptcha" type="text" name="captcha_word"
                       autocomplete="off" required style="max-width: 220px">
            </div>
        </div>
    <?php endif; ?>

    <div class="form-check mt-3">
        <input class="form-check-input" id="nlbFeedbackConsent" type="checkbox" name="CONSENT" value="Y" required>
        <label class="form-check-label small" for="nlbFeedbackConsent">
            Согласен на обработку персональных данных
        </label>
        <div class="invalid-feedback">Нужно согласие.</div>
    </div>

    <button class="btn btn-nlb w-100 mt-3 btn-icon" type="submit" name="submit" value="Y">
        <?= $isBooking ? 'Отправить заявку' : 'Отправить' ?>
        <svg class="i" aria-hidden="true"><use href="#i-send"/></svg>
    </button>
    <?php if ($isBooking): ?>
        <p class="form-hint mt-3 mb-0 text-center">
            * Обязательные поля. Мы свяжемся с вами в течение часа для подтверждения брони.
        </p>
    <?php endif; ?>
</form>
