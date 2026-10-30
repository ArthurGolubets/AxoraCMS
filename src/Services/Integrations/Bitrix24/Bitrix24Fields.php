<?php

namespace HolartWeb\AxoraCMS\Services\Integrations\Bitrix24;

/**
 * Fields of CRM records as the mapping editor shows them, custom UF_CRM_* fields
 * included. Always read from the portal, so a field added there appears right away.
 */
class Bitrix24Fields
{
    /** Filled by the integration itself, a mapping must not overwrite them. */
    private const LINK_FIELDS = ['ID', 'COMPANY_ID', 'COMPANY_IDS', 'CONTACT_ID', 'CONTACT_IDS', 'LEAD_ID'];

    public function __construct(private Bitrix24Client $client) {}

    /**
     * @return array<string, array<int, array{code: string, label: string, type: string, required: bool, custom: bool, options: array<int, array{id: string, label: string}>}>>
     *
     * @throws Bitrix24Exception
     */
    public function catalog(): array
    {
        $result = $this->client->batch([
            'lead' => 'crm.lead.fields',
            'deal' => 'crm.deal.fields',
            'contact' => 'crm.contact.fields',
            'company' => 'crm.company.fields',
            'categories' => 'crm.category.list?entityTypeId=2',
        ]);

        $statuses = $this->client->all('crm.status.list');
        $categories = collect($result['categories']['categories'] ?? [])
            ->map(fn (array $category) => ['id' => (string) $category['id'], 'label' => (string) $category['name']])
            ->values()->all();

        $catalog = [];

        foreach (['lead', 'deal', 'contact', 'company'] as $entity) {
            $catalog[$entity] = $this->normalize((array) ($result[$entity] ?? []), $statuses, $categories);
        }

        return $catalog;
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @param  array<int, array<string, mixed>>  $statuses
     * @param  array<int, array{id: string, label: string}>  $categories
     * @return array<int, array<string, mixed>>
     */
    private function normalize(array $fields, array $statuses, array $categories): array
    {
        $result = [];

        foreach ($fields as $code => $field) {
            if (! empty($field['isReadOnly']) || in_array($code, self::LINK_FIELDS, true)) {
                continue;
            }

            $custom = str_starts_with($code, 'UF_');
            $result[] = [
                'code' => $code,
                'label' => (string) ($field['formLabel'] ?? $field['listLabel'] ?? $field['title'] ?? $code),
                'type' => (string) ($field['type'] ?? 'string'),
                'required' => ! empty($field['isRequired']),
                'custom' => $custom,
                'options' => $code === 'CATEGORY_ID' ? $categories : $this->options($field, $statuses),
            ];
        }

        usort($result, fn (array $a, array $b) => [$a['custom'], $a['label']] <=> [$b['custom'], $b['label']]);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<int, array<string, mixed>>  $statuses
     * @return array<int, array{id: string, label: string}>
     */
    private function options(array $field, array $statuses): array
    {
        if (! empty($field['items'])) {
            return array_map(
                fn (array $item) => ['id' => (string) $item['ID'], 'label' => (string) $item['VALUE']],
                array_values((array) $field['items'])
            );
        }

        if (($field['type'] ?? '') !== 'crm_status' || empty($field['statusType'])) {
            return [];
        }

        $type = (string) $field['statusType'];

        // Stages of every pipeline: DEAL_STAGE for the default one, DEAL_STAGE_<id> for the rest.
        return collect($statuses)
            ->filter(fn (array $status) => $status['ENTITY_ID'] === $type || ($type === 'DEAL_STAGE' && str_starts_with((string) $status['ENTITY_ID'], 'DEAL_STAGE_')))
            ->map(fn (array $status) => ['id' => (string) $status['STATUS_ID'], 'label' => (string) $status['NAME']])
            ->values()->all();
    }
}
