<?php
/**
 * 004 — разделы инфоблоков.
 *
 * Раздел ищется по CODE внутри инфоблока. XML_ID раздела —
 * nlb-sec-<инфоблок>-<код>, чтобы откат мог удалить только свои разделы.
 */

return static function (): array {
    $config = nlb_load_config('iblocks');
    $created = 0;
    $skipped = 0;
    $notes = [];

    foreach ($config['iblocks'] as $code => $iblock) {
        if (empty($iblock['sections_data'])) {
            continue;
        }
        $iblockId = nlb_iblock_id($code);
        if ($iblockId <= 0) {
            throw new RuntimeException("разделы {$code}: инфоблок не найден (сначала 002)");
        }

        foreach ($iblock['sections_data'] as $section) {
            $sectionCode = (string)$section['code'];
            if (nlb_section_id($iblockId, $sectionCode) > 0) {
                $skipped++;
                continue;
            }

            $object = new CIBlockSection();
            $id = (int)$object->Add([
                'IBLOCK_ID' => $iblockId,
                'NAME' => $section['name'],
                'CODE' => $sectionCode,
                'XML_ID' => 'nlb-sec-' . $code . '-' . $sectionCode,
                'ACTIVE' => 'Y',
                'SORT' => (int)($section['sort'] ?? 100),
                'DESCRIPTION_TYPE' => 'text',
            ]);

            if ($id <= 0) {
                $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
                throw new RuntimeException("раздел {$code}.{$sectionCode}: " . ($error ?: 'не удалось создать'));
            }

            $created++;
            $notes[] = "создан раздел {$code}.{$sectionCode} (ID {$id})";
        }
    }

    return ['created' => $created, 'skipped' => $skipped, 'notes' => $notes];
};
