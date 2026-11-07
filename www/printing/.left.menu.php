<?php
/**
 * Левое меню раздела «Печать» (тип left).
 *
 * Копируется миграцией 011_section_menus.php в /printing/.left.menu.php.
 * Компонент bitrix:menu с MAX_LEVEL = 2 и CHILD_MENU_TYPE = left берёт
 * отсюда выпадающий список верхнего пункта «Печать».
 *
 * В списке — восемь первых услуг каталога (`db/config/data/printing.php`),
 * остальные доступны на странице каталога.
 *
 * Формат пункта: [текст, ссылка, доп. ссылки, параметры, условие показа].
 */

$aMenuLinks = [
    ['Все услуги печати', '/printing/', [], [], ''],
    ['Печать и копирование', '/printing/pechat-dokumentov/', [], [], ''],
    ['Сканирование', '/printing/skanirovanie/', [], [], ''],
    ['Переплёт и ламинирование', '/printing/pereplyot-i-laminirovanie/', [], [], ''],
    ['Визитки', '/printing/vizitki/', [], [], ''],
    ['Брошюры и буклеты', '/printing/broshyury-i-buklety/', [], [], ''],
    ['Широкоформатная печать', '/printing/shirokiy-format/', [], [], ''],
    ['Печать на холсте', '/printing/pechat-na-holste/', [], [], ''],
    ['Печать на кружках', '/printing/kruzhki/', [], [], ''],
];
