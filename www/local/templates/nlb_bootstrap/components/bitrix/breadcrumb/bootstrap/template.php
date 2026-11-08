<?php
/**
 * Шаблон компонента bitrix:breadcrumb → разметка под styles.css (.breadcrumb-navigation).
 *
 * Компонент вызывает шаблон через $APPLICATION->AddBufferContent() с
 * $bIncludeOnce = true: разметку собирает CMain::_mkchain() и возвращает её
 * как строку. Поэтому шаблон обязан ВЕРНУТЬ строку, а не печатать её:
 * при печати вывод уходит в буфер в момент его сброса (крошки оказываются
 * в начале документа), а в контейнер вместо крошек попадает «1» — результат
 * include. Так же устроен штатный шаблон breadcrumb/universal.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (empty($arResult) || !is_array($arResult)) {
    return '';
}

$items = '';
foreach ($arResult as $item) {
    $title = htmlspecialcharsbx(isset($item['TITLE']) ? $item['TITLE'] : '');
    $link = isset($item['LINK']) ? (string)$item['LINK'] : '';

    if ($link !== '') {
        $items .= '<li><a href="' . htmlspecialcharsbx($link) . '">' . $title . '</a></li>';
    } else {
        $items .= '<li><span aria-current="page">' . $title . '</span></li>';
    }
}

if ($items === '') {
    return '';
}

return '<nav aria-label="Хлебные крошки"><ol class="breadcrumb-navigation">' . $items . '</ol></nav>';
