<?php
/**
 * 002 — инфоблоки.
 *
 * Создаёт news, events, collections, books, services, printing и привязывает
 * их к сайту (SITE_ID). Права: группа 2 («Все пользователи») — чтение,
 * чтобы компоненты работали для гостей.
 *
 * Версия инфоблока — 2 (свойства в отдельной таблице), как в новых
 * установках Битрикса.
 */

return static function (): array {
    $config = nlb_load_config('iblocks');
    $siteId = nlb_site_id();
    $created = 0;
    $skipped = 0;
    $notes = [];

    foreach ($config['iblocks'] as $code => $iblock) {
        if (nlb_iblock_id($code) > 0) {
            $skipped++;
            $notes[] = "инфоблок {$code} уже есть";
            continue;
        }

        $fields = [
            'ACTIVE' => 'Y',
            'NAME' => $iblock['name'],
            'CODE' => $code,
            'IBLOCK_TYPE_ID' => $iblock['type'],
            'SITE_ID' => [$siteId],
            'SORT' => (int)$iblock['sort'],
            'LIST_PAGE_URL' => $iblock['list_page_url'],
            'DETAIL_PAGE_URL' => $iblock['detail_page_url'],
            'SECTION_PAGE_URL' => $iblock['section_page_url'],
            'INDEX_ELEMENT' => $iblock['index'] ?? 'Y',
            'INDEX_SECTION' => $iblock['sections'],
            'WORKFLOW' => 'N',
            'BIZPROC' => 'N',
            'SECTION_CHOOSER' => 'L',
            'LIST_MODE' => '',
            'VERSION' => 2,
            'GROUP_ID' => $iblock['groups'] ?? ['2' => 'R'],
            'FIELDS' => [
                'CODE' => [
                    'IS_REQUIRED' => 'Y',
                    'DEFAULT_VALUE' => [
                        'TRANSLITERATION' => 'Y',
                        'TRANS_LEN' => 100,
                        'TRANS_CASE' => 'L',
                        'TRANS_SPACE' => '-',
                        'TRANS_OTHER' => '-',
                        'TRANS_EAT' => 'Y',
                        'USE_GOOGLE' => 'N',
                    ],
                ],
                'PREVIEW_PICTURE' => [
                    'IS_REQUIRED' => 'N',
                    'DEFAULT_VALUE' => [
                        'FROM_DETAIL' => 'Y',
                        'SCALE' => 'Y',
                        'WIDTH' => 600,
                        'HEIGHT' => 400,
                        'IGNORE_ERRORS' => 'N',
                        'METHOD' => 'resample',
                        'COMPRESS' => 80,
                        'DELETE_WITH_DETAIL' => 'N',
                        'UPDATE_WITH_DETAIL' => 'N',
                    ],
                ],
                'DETAIL_PICTURE' => [
                    'IS_REQUIRED' => 'N',
                    'DEFAULT_VALUE' => [
                        'SCALE' => 'Y',
                        'WIDTH' => 1200,
                        'HEIGHT' => 800,
                        'IGNORE_ERRORS' => 'N',
                        'METHOD' => 'resample',
                        'COMPRESS' => 85,
                        'DELETE_WITH_DETAIL' => 'N',
                        'UPDATE_WITH_DETAIL' => 'N',
                    ],
                ],
            ],
        ];

        $object = new CIBlock();
        $id = (int)$object->Add($fields);
        if ($id <= 0) {
            $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
            throw new RuntimeException("инфоблок {$code}: " . ($error ?: 'не удалось создать'));
        }

        $created++;
        $notes[] = "создан инфоблок {$code} (ID {$id})";
    }

    return ['created' => $created, 'skipped' => $skipped, 'notes' => $notes];
};
