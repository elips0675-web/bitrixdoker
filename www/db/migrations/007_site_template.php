<?php
/**
 * 007 — привязка шаблона nlb_bootstrap к сайту (опционально).
 *
 * По умолчанию шаг пропускается: он меняет настройки сайта, а шаблон
 * должен быть уже загружен в local/templates/. Включение:
 *
 *   php db/run.php --only=007 --set-template
 *
 * Перед изменением текущий список шаблонов пишется в лог.
 */

return static function (): array {
    if (!nlb_option('set_template', false)) {
        return [
            'created' => 0,
            'skipped' => 1,
            'notes' => ['привязка шаблона пропущена (нужен флаг --set-template)'],
        ];
    }

    $siteId = nlb_site_id();
    $notes = [];

    /* Текущие шаблоны — в лог, чтобы можно было вернуть. */
    $current = [];
    $templates = CSite::GetTemplateList($siteId);
    while ($template = $templates->Fetch()) {
        $current[] = (string)($template['TEMPLATE'] ?? '') . ' | условие: '
            . (string)($template['CONDITION'] ?? '') . ' | сорт: '
            . (string)($template['SORT'] ?? '');
    }
    $notes[] = 'шаблоны до изменения: ' . ($current ? implode('; ', $current) : 'не прочитаны');

    $object = new CSite();
    $updated = $object->Update($siteId, [
        'TEMPLATE' => [
            [
                'CONDITION' => '',
                'SORT' => 1,
                'TEMPLATE' => 'nlb_bootstrap',
            ],
        ],
    ]);

    if (!$updated) {
        $error = isset($object->LAST_ERROR) ? (string)$object->LAST_ERROR : '';
        throw new RuntimeException('привязка шаблона: ' . ($error ?: 'не удалось обновить сайт'));
    }

    $notes[] = "сайт {$siteId}: шаблон nlb_bootstrap привязан";

    return ['created' => 1, 'skipped' => 0, 'notes' => $notes];
};
