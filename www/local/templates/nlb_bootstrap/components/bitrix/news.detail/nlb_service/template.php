<?php
/**
 * Детальная страница услуги: bitrix:news.detail → nlb_service.
 *
 * Повторяет макет референса: назад к списку, иконка + название + подпись,
 * текст, карточки режима работы и контактов, галерея, что входит в услугу
 * и тарифы. Форма бронирования подключается на странице services/detail.php,
 * чтобы не кэшировать sessid и поля заявки.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);

$item = is_array($arResult) ? $arResult : [];
if (!$item) {
    echo '<div class="empty-state">Услуга не найдена.</div>';
    return;
}

$name = (string)($item['NAME'] ?? '');
$detail = (string)($item['DETAIL_TEXT'] ?? '');
$preview = (string)($item['PREVIEW_TEXT'] ?? '');
$properties = isset($item['PROPERTIES']) && is_array($item['PROPERTIES']) ? $item['PROPERTIES'] : [];

$icon = nlb_value($item, 'ICON', 'sparkles');
$color = nlb_grad_class(nlb_xml_id($item, 'COLOR'));
$subtitle = nlb_value($item, 'SUBTITLE');
$schedule = nlb_values($item, 'SCHEDULE');
$phone = nlb_value($item, 'PHONE');
$email = nlb_value($item, 'EMAIL');
$features = nlb_values($item, 'FEATURES');
$tariffs = nlb_values($item, 'TARIFFS');

$picture = '';
foreach (['DETAIL_PICTURE', 'PREVIEW_PICTURE'] as $key) {
    if (!empty($item[$key]['SRC'])) {
        $picture = (string)$item[$key]['SRC'];
        break;
    }
}

$backUrl = (string)($arParams['NLB_BACK_URL'] ?? SITE_DIR . 'services/');
$backText = (string)($arParams['NLB_BACK_TEXT'] ?? 'Назад к услугам');

$featureItems = [];
foreach ($features as $feature) {
    $parts = explode('|', $feature, 2);
    $title = trim($parts[0]);
    if ($title === '') {
        continue;
    }
    $featureItems[] = [
        'title' => $title,
        'text' => trim($parts[1] ?? ''),
    ];
}

$tariffItems = [];
foreach ($tariffs as $tariff) {
    $parts = array_map('trim', explode('|', $tariff));
    $title = (string)($parts[0] ?? '');
    if ($title === '') {
        continue;
    }
    $includes = [];
    if (!empty($parts[3])) {
        foreach (explode(';', $parts[3]) as $one) {
            $one = trim($one);
            if ($one !== '') {
                $includes[] = $one;
            }
        }
    }
    if (!empty($parts[2])) {
        array_unshift($includes, (string)$parts[2]);
    }
    $tariffItems[] = [
        'title' => $title,
        'price' => (string)($parts[1] ?? ''),
        'includes' => array_values(array_unique($includes)),
        'popular' => trim((string)($parts[4] ?? '')) !== '',
    ];
}
?>
<article class="service-detail">
    <a class="service-back" href="<?= htmlspecialcharsbx($backUrl) ?>">
        <svg class="i" aria-hidden="true"><use href="#i-chevron-left"/></svg>
        <?= htmlspecialcharsbx($backText) ?>
    </a>

    <header class="service-detail-head">
        <span class="service-detail-icon bg-grad-<?= htmlspecialcharsbx($color) ?>">
            <svg class="i" aria-hidden="true"><use href="#i-<?= htmlspecialcharsbx($icon) ?>"/></svg>
        </span>
        <div>
            <h1 class="page-title mb-1"><?= htmlspecialcharsbx($name) ?></h1>
            <?php if ($subtitle !== ''): ?>
                <p class="service-detail-subtitle"><?= htmlspecialcharsbx($subtitle) ?></p>
            <?php endif; ?>
        </div>
    </header>

    <div class="service-detail-body bitrix-content">
        <?php if ($detail !== ''): ?>
            <?= $detail ?>
        <?php elseif ($preview !== ''): ?>
            <p><?= htmlspecialcharsbx($preview) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($schedule || $phone !== '' || $email !== ''): ?>
        <div class="service-meta">
            <?php if ($schedule): ?>
                <div class="service-meta-card service-meta-card--<?= htmlspecialcharsbx($color) ?>">
                    <div class="service-meta-head">
                        <svg class="i" aria-hidden="true"><use href="#i-clock"/></svg>
                        <h2>Режим работы</h2>
                    </div>
                    <div class="service-meta-lines">
                        <?php foreach ($schedule as $line): ?>
                            <?php
                            // «Понедельник – Пятница: 10:00 – 22:00» → жирные дни.
                            $scheduleParts = explode(':', $line, 2);
                            $scheduleDay = trim($scheduleParts[0]);
                            $scheduleTime = trim($scheduleParts[1] ?? '');
                            ?>
                            <div>
                                <?php if ($scheduleTime !== ''): ?>
                                    <span class="service-meta-strong"><?= htmlspecialcharsbx($scheduleDay) ?>:</span>
                                    <?= htmlspecialcharsbx($scheduleTime) ?>
                                <?php else: ?>
                                    <?= htmlspecialcharsbx($line) ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($phone !== '' || $email !== ''): ?>
                <div class="service-meta-card service-meta-card--contacts">
                    <div class="service-meta-head">
                        <svg class="i" aria-hidden="true"><use href="#i-phone"/></svg>
                        <h2>Контакты</h2>
                    </div>
                    <div class="service-meta-lines">
                        <?php if ($phone !== ''): ?>
                            <div>
                                <a href="tel:<?= htmlspecialcharsbx((string)preg_replace('/[^+\d]/', '', $phone)) ?>">
                                    <?= htmlspecialcharsbx($phone) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if ($email !== ''): ?>
                            <div>
                                <a href="mailto:<?= htmlspecialcharsbx($email) ?>"><?= htmlspecialcharsbx($email) ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</article>

<section class="service-section">
    <div class="section-head">
        <h2 class="section-title">Галерея</h2>
        <p class="section-lead">Фотографии пространства. Присылайте свои снимки — добавим в галерею.</p>
    </div>
    <div class="service-gallery">
        <?php if ($picture !== ''): ?>
            <figure class="service-gallery-item service-gallery-item--wide">
                <?= nlb_picture($picture, $name, ['sizes' => '(max-width: 991px) 50vw, 360px']) ?>
            </figure>
        <?php endif; ?>
        <?php for ($i = $picture !== '' ? 1 : 0; $i < 3; $i++): ?>
            <div class="service-gallery-item service-gallery-empty" aria-hidden="true">
                <svg class="i" aria-hidden="true"><use href="#i-<?= htmlspecialcharsbx($icon) ?>"/></svg>
                <span>Фото скоро появится</span>
            </div>
        <?php endfor; ?>
    </div>
    <div class="service-gallery-actions">
        <a class="btn btn-nlb-outline btn-icon" href="<?= SITE_DIR ?>feedback/">
            <svg class="i" aria-hidden="true"><use href="#i-plus"/></svg>
            Предложить фото
        </a>
    </div>
</section>

<?php if ($featureItems): ?>
    <section class="service-section">
        <div class="section-head">
            <h2 class="section-title">Что входит в услугу</h2>
        </div>
        <div class="row g-4">
            <?php foreach ($featureItems as $feature): ?>
                <div class="col-sm-6 col-lg-4">
                    <article class="service-feature-item h-100">
                        <span class="service-feature-item-icon bg-grad-<?= htmlspecialcharsbx($color) ?>">
                            <svg class="i" aria-hidden="true"><use href="#i-<?= htmlspecialcharsbx($icon) ?>"/></svg>
                        </span>
                        <h3><?= htmlspecialcharsbx($feature['title']) ?></h3>
                        <?php if ($feature['text'] !== ''): ?>
                            <p><?= htmlspecialcharsbx($feature['text']) ?></p>
                        <?php endif; ?>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($tariffItems): ?>
    <section class="service-section">
        <div class="section-head">
            <h2 class="section-title">Цены и тарифы</h2>
        </div>
        <div class="row g-4 align-items-stretch">
            <?php foreach ($tariffItems as $tariff): ?>
                <div class="col-md-6 col-lg-4 d-flex">
                    <article class="tariff-card h-100<?= $tariff['popular'] ? ' tariff-card--popular' : '' ?>">
                        <?php if ($tariff['popular']): ?>
                            <span class="tariff-badge">Популярный</span>
                        <?php endif; ?>
                        <h3><?= htmlspecialcharsbx($tariff['title']) ?></h3>
                        <p class="tariff-price"><?= htmlspecialcharsbx($tariff['price']) ?></p>
                        <?php if ($tariff['includes']): ?>
                            <ul class="tariff-list">
                                <?php foreach ($tariff['includes'] as $include): ?>
                                    <li>
                                        <svg class="i" aria-hidden="true"><use href="#i-check"/></svg>
                                        <?= htmlspecialcharsbx($include) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <a class="btn btn-nlb w-100 mt-auto js-choose-tariff"
                           href="#booking"
                           data-tariff="<?= htmlspecialcharsbx($tariff['title']) ?>"
                           data-price="<?= htmlspecialcharsbx($tariff['price']) ?>">
                            Выбрать тариф
                        </a>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
