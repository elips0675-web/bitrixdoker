<?php
/**
 * Универсальный шаблон детальной страницы: bitrix:news.detail → nlb_detail.
 *
 * Подходит для новостей, событий, услуг, печати, книг и коллекций:
 * заголовок, картинка, текст и таблица свойств строятся по данным элемента.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);

$item = is_array($arResult) ? $arResult : [];
if (!$item) {
    echo '<div class="empty-state">Элемент не найден.</div>';
    return;
}

$name = (string)($item['NAME'] ?? '');
$detail = (string)($item['DETAIL_TEXT'] ?? '');
$preview = (string)($item['PREVIEW_TEXT'] ?? '');
$picture = '';
foreach (['DETAIL_PICTURE', 'PREVIEW_PICTURE'] as $key) {
    if (!empty($item[$key]['SRC'])) {
        $picture = (string)$item[$key]['SRC'];
        break;
    }
}
$properties = isset($item['PROPERTIES']) && is_array($item['PROPERTIES']) ? $item['PROPERTIES'] : [];

/*
 * Товар (сувенир) отличается от новости набором свойств: у него есть цена,
 * а AUTHOR хранит материал. Для товара материал выводим в таблице свойств,
 * а не в строке метаданных, и показываем цену без приставки «от».
 */
$isProduct = isset($properties['PRICE'])
    || (string)($properties['AUTHOR']['NAME'] ?? '') === 'Материал';

$date = nlb_value($item, 'DATE');
$timestamp = $date !== '' ? strtotime($date) : false;
$author = $isProduct ? '' : nlb_value($item, 'AUTHOR');
$readTime = nlb_value($item, 'READ_TIME');
$register = nlb_value($item, 'REGISTER_URL');
if ($isProduct) {
    $priceNumber = (int)($properties['PRICE']['VALUE'] ?? 0);
    $price = $priceNumber > 0 ? number_format($priceNumber, 0, '', ' ') . ' ₽' : '';
} else {
    $price = nlb_price(nlb_value($item, 'PRICE_FROM'));
    if ($price === '') {
        $price = nlb_value($item, 'PRICE');
    }
}
$unit = nlb_value($item, 'UNIT');

$skip = ['ICON', 'COLOR', 'TAGS', 'DATE', 'AUTHOR', 'READ_TIME', 'REGISTER_URL', 'PRICE_FROM', 'PRICE', 'UNIT'];
if ($isProduct) {
    $skip = array_values(array_diff($skip, ['AUTHOR']));
}
$rows = [];
foreach ($properties as $code => $property) {
    if (in_array($code, $skip, true)) {
        continue;
    }
    $value = $property['VALUE'] ?? '';
    if (is_array($value)) {
        $value = implode(', ', array_filter($value, static function ($one) {
            return is_scalar($one) && (string)$one !== '';
        }));
    }
    $value = trim((string)$value);
    if ($value === '') {
        continue;
    }
    $rows[] = [
        'name' => (string)($property['NAME'] ?? $code),
        'value' => $value,
    ];
}

$tags = [];
$tagProperty = $properties['TAGS'] ?? null;
if (is_array($tagProperty) && !empty($tagProperty['VALUE'])) {
    $tagValues = is_array($tagProperty['VALUE']) ? $tagProperty['VALUE'] : [$tagProperty['VALUE']];
    foreach ($tagValues as $tag) {
        $tag = trim((string)$tag);
        if ($tag !== '') {
            $tags[] = $tag;
        }
    }
}
?>
<article class="article">
    <header class="article-head">
        <?php if ($timestamp): ?>
            <p class="article-meta">
                <svg class="i i-20" aria-hidden="true"><use href="#i-calendar"/></svg>
                <?= FormatDate('j F Y', $timestamp) ?>
                <?php if ($readTime !== ''): ?>
                    <span class="article-dot" aria-hidden="true">·</span> <?= htmlspecialcharsbx($readTime) ?>
                <?php endif; ?>
                <?php if ($author !== ''): ?>
                    <span class="article-dot" aria-hidden="true">·</span> <?= htmlspecialcharsbx($author) ?>
                <?php endif; ?>
            </p>
        <?php elseif ($author !== '' || $readTime !== ''): ?>
            <p class="article-meta">
                <?= htmlspecialcharsbx(trim($author . ($readTime !== '' ? ' · ' . $readTime : ''))) ?>
            </p>
        <?php endif; ?>

        <h1 class="page-title"><?= htmlspecialcharsbx($name) ?></h1>

        <?php if ($preview !== ''): ?>
            <p class="section-lead"><?= htmlspecialcharsbx($preview) ?></p>
        <?php endif; ?>

        <?php if ($tags): ?>
            <ul class="tag-list" aria-label="Теги">
                <?php foreach ($tags as $tag): ?>
                    <li><?= htmlspecialcharsbx($tag) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </header>

    <?php if ($picture !== ''): ?>
        <figure class="article-cover">
            <?= nlb_picture($picture, $name, ['sizes' => '(max-width: 991px) 100vw, 760px']) ?>
        </figure>
    <?php endif; ?>

    <div class="article-body">
        <div class="bitrix-content">
            <?php if ($detail !== ''): ?>
                <?= $detail ?>
            <?php elseif ($preview !== ''): ?>
                <p><?= htmlspecialcharsbx($preview) ?></p>
            <?php endif; ?>
        </div>

        <?php if ($rows || $price !== '' || $timestamp || $register !== ''): ?>
            <aside class="article-aside">
                <h2 class="h5 mb-3">Коротко</h2>
                <dl class="article-props">
                    <?php if ($timestamp): ?>
                        <div>
                            <dt>Когда</dt>
                            <dd><?= FormatDate('j F Y, H:i', $timestamp) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($price !== ''): ?>
                        <div>
                            <dt><?= $isProduct ? 'Цена' : 'Стоимость' ?></dt>
                            <dd><?= htmlspecialcharsbx($price) ?><?= $unit !== '' ? ' · ' . htmlspecialcharsbx($unit) : '' ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <div>
                            <dt><?= htmlspecialcharsbx($row['name']) ?></dt>
                            <dd><?= htmlspecialcharsbx($row['value']) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>

                <?php if ($isProduct): ?>
                    <a class="btn btn-nlb-green w-100 mt-3 btn-icon" href="#order">
                        Заказать
                        <svg class="i" aria-hidden="true"><use href="#i-package"/></svg>
                    </a>
                <?php endif; ?>

                <?php if ($register !== ''): ?>
                    <a class="btn btn-nlb-green w-100 mt-3" href="<?= htmlspecialcharsbx($register) ?>">
                        Зарегистрироваться
                    </a>
                <?php endif; ?>
            </aside>
        <?php endif; ?>
    </div>

    <?php
    $backUrl = (string)($arParams['NLB_BACK_URL'] ?? SITE_DIR);
    $backText = (string)($arParams['NLB_BACK_TEXT'] ?? 'На главную');
    ?>
    <footer class="article-footer">
        <a class="btn btn-nlb-outline btn-icon" href="<?= htmlspecialcharsbx($backUrl) ?>">
            <svg class="i" aria-hidden="true"><use href="#i-chevron-left"/></svg>
            <?= htmlspecialcharsbx($backText) ?>
        </a>
    </footer>
</article>
