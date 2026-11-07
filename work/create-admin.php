<?php
/**
 * РЎРѕР·РґР°РЅРёРµ Р°РґРјРёРЅРёСЃС‚СЂР°С‚РѕСЂР° РЅР° СЃРІРµР¶РµСѓСЃС‚Р°РЅРѕРІР»РµРЅРЅРѕРј Р‘РёС‚СЂРёРєСЃРµ.
 * Р—Р°РїСѓСЃРє: php /var/www/html/_setup/create-admin.php
 */

$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
$_SERVER['REQUEST_METHOD'] = 'GET';
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$login = 'admin';
$password = 'Bitrix!2026';
$email = 'admin@example.com';

$exists = CUser::GetByLogin($login)->Fetch();
if ($exists) {
    $u = new CUser();
    $u->Update((int)$exists['ID'], ['PASSWORD' => $password, 'CONFIRM_PASSWORD' => $password, 'ACTIVE' => 'Y']);
    echo "UPDATED id=" . $exists['ID'] . "\n";
} else {
    $u = new CUser();
    $id = $u->Add([
        'LOGIN' => $login,
        'NAME' => 'РђРґРјРёРЅРёСЃС‚СЂР°С‚РѕСЂ',
        'LAST_NAME' => 'РЎС‚РµРЅРґР°',
        'EMAIL' => $email,
        'PASSWORD' => $password,
        'CONFIRM_PASSWORD' => $password,
        'GROUP_ID' => [1],
        'ACTIVE' => 'Y',
        'LID' => 's1',
    ]);
    if (!$id) {
        fwrite(STDERR, 'ERROR: ' . $u->LAST_ERROR . "\n");
        exit(1);
    }
    echo "CREATED id=$id\n";
}

$groups = [];
$res = CGroup::GetList('c_sort', 'asc', ['ID' => 1]);
while ($g = $res->Fetch()) {
    $groups[] = $g['ID'] . ':' . $g['NAME'];
}
echo 'ADMIN_GROUPS=' . implode(',', $groups) . "\n";
echo "LOGIN=$login PASSWORD=$password\n";

