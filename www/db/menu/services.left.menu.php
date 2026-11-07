<?php
/**
 * Левое меню раздела «Услуги» (тип left).
 *
 * Копируется миграцией 011_section_menus.php в /services/.left.menu.php.
 * Компонент bitrix:menu с MAX_LEVEL = 2 и CHILD_MENU_TYPE = left берёт
 * отсюда выпадающий список верхнего пункта «Услуги».
 *
 * Формат пункта: [текст, ссылка, доп. ссылки, параметры, условие показа].
 */

$aMenuLinks = [
    ['Все услуги', '/services/', [], [], ''],
    ['Обзорная площадка', '/services/observation-deck/', [], [], ''],
    ['Сауна', '/services/sauna/', [], [], ''],
    ['Кафе', '/services/cafe/', [], [], ''],
    ['Лекторий', '/services/lecture-hall/', [], [], ''],
    ['Концертный зал', '/services/concert-hall/', [], [], ''],
    ['Детская комната', '/services/kids-zone/', [], [], ''],
    ['Спортзал', '/services/gym/', [], [], ''],
    ['Коворкинг', '/services/coworking/', [], [], ''],
];
