<?php
/**
 * Шаблон компонента bitrix:breadcrumb → разметка под styles.css (.breadcrumb-navigation).
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (method_exists($this, 'setFrameMode')) {
    $this->setFrameMode(true);
}

if (empty($arResult) || !is_array($arResult)) {
    return;
}
?>
<nav aria-label="Хлебные крошки">
    <ol class="breadcrumb-navigation">
        <?php foreach ($arResult as $item):
            $title = isset($item['TITLE']) ? $item['TITLE'] : '';
            $link = isset($item['LINK']) ? (string)$item['LINK'] : '';
            ?>
            <li>
                <?php if ($link !== ''): ?>
                    <a href="<?= htmlspecialcharsbx($link) ?>"><?= $title ?></a>
                <?php else: ?>
                    <span aria-current="page"><?= $title ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
