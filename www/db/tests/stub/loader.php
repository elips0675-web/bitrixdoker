<?php
/**
 * Заглушка \Bitrix\Main\Loader для автотеста миграций.
 */

namespace Bitrix\Main;

class Loader
{
    public static function includeModule(string $module): bool
    {
        return $module === 'iblock' || $module === 'main' || $module === 'fileman';
    }
}
