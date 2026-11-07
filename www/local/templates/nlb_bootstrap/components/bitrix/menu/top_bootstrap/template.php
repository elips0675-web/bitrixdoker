<?php
/**
 * Шаблон компонента bitrix:menu → Bootstrap navbar с выпадающими списками.
 * Подключение: $APPLICATION->IncludeComponent('bitrix:menu', 'top_bootstrap', [...]);
 *
 * Второй уровень приходит из .left.menu.php раздела (CHILD_MENU_TYPE = left,
 * MAX_LEVEL = 2): компонент отдаёт плоский список с DEPTH_LEVEL и IS_PARENT.
 * Шаблон собирает из него дерево и рисует пункты с подменю как dropdown.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);

$items = is_array($arResult) ? $arResult : [];

/* Подпункты демо-меню: пока в разделе нет файла .left.menu.php. */
$demoChildren = [
    SITE_DIR . 'services/' => [
        ['Все услуги', SITE_DIR . 'services/'],
        ['Обзорная площадка', SITE_DIR . 'services/observation-deck/'],
        ['Сауна', SITE_DIR . 'services/sauna/'],
        ['Кафе', SITE_DIR . 'services/cafe/'],
        ['Лекторий', SITE_DIR . 'services/lecture-hall/'],
        ['Концертный зал', SITE_DIR . 'services/concert-hall/'],
        ['Детская комната', SITE_DIR . 'services/kids-zone/'],
        ['Спортзал', SITE_DIR . 'services/gym/'],
        ['Коворкинг', SITE_DIR . 'services/coworking/'],
    ],
    SITE_DIR . 'printing/' => [
        ['Все услуги печати', SITE_DIR . 'printing/'],
        ['Печать и копирование', SITE_DIR . 'printing/pechat-dokumentov/'],
        ['Сканирование', SITE_DIR . 'printing/skanirovanie/'],
        ['Переплёт и ламинирование', SITE_DIR . 'printing/pereplyot-i-laminirovanie/'],
        ['Визитки', SITE_DIR . 'printing/vizitki/'],
        ['Брошюры и буклеты', SITE_DIR . 'printing/broshyury-i-buklety/'],
        ['Широкоформатная печать', SITE_DIR . 'printing/shirokiy-format/'],
        ['Печать на холсте', SITE_DIR . 'printing/pechat-na-holste/'],
        ['Печать на кружках', SITE_DIR . 'printing/kruzhki/'],
    ],
];

// Демо-меню: нужно, чтобы шаблон выглядел готовым до создания типа меню "top".
if (empty($items)) {
    $current = $APPLICATION->GetCurPage(false);
    $demo = [
        ['TEXT' => 'Главная', 'LINK' => SITE_DIR],
        ['TEXT' => 'Новости', 'LINK' => SITE_DIR . 'news/'],
        ['TEXT' => 'События', 'LINK' => SITE_DIR . 'events/'],
        ['TEXT' => 'Услуги', 'LINK' => SITE_DIR . 'services/'],
        ['TEXT' => 'Печать', 'LINK' => SITE_DIR . 'printing/'],
        ['TEXT' => 'Каталог продукции', 'LINK' => SITE_DIR . 'books/'],
        ['TEXT' => 'Контакты', 'LINK' => SITE_DIR . 'contacts/'],
    ];

    foreach ($demo as $item) {
        $link = rtrim($item['LINK'], '/');
        $isHome = $link === rtrim(SITE_DIR, '/');
        $items[] = [
            'TEXT' => $item['TEXT'],
            'LINK' => $item['LINK'],
            'SELECTED' => $isHome
                ? rtrim($current, '/') === $link
                : $link !== '' && strpos($current, $link . '/') === 0,
            'DEPTH_LEVEL' => 1,
        ];

        foreach ($demoChildren[$item['LINK']] ?? [] as [$text, $childLink]) {
            $childBase = rtrim($childLink, '/');
            $items[] = [
                'TEXT' => $text,
                'LINK' => $childLink,
                'SELECTED' => $childBase !== '' && $childBase !== $link
                    && strpos($current, $childBase . '/') === 0,
                'DEPTH_LEVEL' => 2,
            ];
        }
    }
}

/* Дерево: пункты DEPTH_LEVEL > 1 вкладываются в ближайший пункт первого уровня. */
$tree = [];
$parentIndex = null;
foreach ($items as $item) {
    if ((int)($item['DEPTH_LEVEL'] ?? 1) > 1) {
        if ($parentIndex !== null) {
            $tree[$parentIndex]['CHILDREN'][] = $item;
        }
        continue;
    }

    $tree[] = [
        'TEXT' => (string)($item['TEXT'] ?? ''),
        'LINK' => (string)($item['LINK'] ?? '#'),
        'SELECTED' => !empty($item['SELECTED']),
        'CHILDREN' => [],
    ];
    $parentIndex = count($tree) - 1;
}

// Родитель подсвечивается, когда открыт любой его подпункт.
foreach ($tree as &$node) {
    foreach ($node['CHILDREN'] as $child) {
        if (!empty($child['SELECTED'])) {
            $node['SELECTED'] = true;
        }
    }
}
unset($node);
?>
<ul class="navbar-nav mx-auto">
    <?php foreach ($tree as $index => $node):
        $isSelected = !empty($node['SELECTED']);
        $link = $node['LINK'] !== '' ? $node['LINK'] : '#';
        $hasChildren = !empty($node['CHILDREN']);
        ?>
        <?php if ($hasChildren): ?>
            <?php $menuId = 'nlb-menu-' . (int)$index; ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle<?= $isSelected ? ' active' : '' ?>"
                   href="<?= htmlspecialcharsbx($link) ?>" id="<?= $menuId ?>"
                   role="button" data-bs-toggle="dropdown" aria-expanded="false"<?= $isSelected ? ' aria-current="page"' : '' ?>>
                    <?= $node['TEXT'] ?>
                </a>
                <ul class="dropdown-menu" aria-labelledby="<?= $menuId ?>">
                    <?php foreach ($node['CHILDREN'] as $child):
                        $childSelected = !empty($child['SELECTED']);
                        ?>
                        <li>
                            <a class="dropdown-item<?= $childSelected ? ' active' : '' ?>"
                               href="<?= htmlspecialcharsbx((string)($child['LINK'] ?? '#')) ?>"<?= $childSelected ? ' aria-current="page"' : '' ?>>
                                <?= $child['TEXT'] ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php else: ?>
            <li class="nav-item">
                <a class="nav-link<?= $isSelected ? ' active' : '' ?>"
                   href="<?= htmlspecialcharsbx($link) ?>"<?= $isSelected ? ' aria-current="page"' : '' ?>>
                    <?= $node['TEXT'] ?>
                </a>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>
