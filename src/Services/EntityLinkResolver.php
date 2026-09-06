<?php

namespace HolartWeb\AxoraCMS\Services;

use HolartWeb\AxoraCMS\Models\Shop\TCatalog;
use HolartWeb\AxoraCMS\Models\Shop\TProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves "entity link" field values (catalog properties / characteristic
 * definitions of type "entity") from stored references into real models.
 *
 * A stored value is a list of references:
 *   [{ "type": "product", "id": 5 }, { "type": "infoblock", "id": 12, "infoblock_id": 3 }]
 *
 * Single (non-multiple) fields are stored the same way with a single element.
 */
class EntityLinkResolver
{
    /**
     * Resolve a raw stored value into a list of hydrated references.
     *
     * @param  mixed  $value  Raw value (array, JSON string or null).
     * @return array<int, array{type: string, id: int, infoblock_id?: int, title: ?string, object: mixed}>
     */
    public function resolve($value): array
    {
        $refs = $this->normalizeRefs($value);

        if (empty($refs)) {
            return [];
        }

        $byType = collect($refs)->groupBy('type');
        $models = [
            'product' => $this->loadProducts($byType->get('product', collect())),
            'catalog' => $this->loadCatalogs($byType->get('catalog', collect())),
            'infoblock' => $this->loadInfoBlockElements($byType->get('infoblock', collect())),
        ];

        return array_map(function (array $ref) use ($models) {
            $object = $models[$ref['type']][$ref['id']] ?? null;

            return [
                'type' => $ref['type'],
                'id' => $ref['id'],
                'infoblock_id' => $ref['infoblock_id'] ?? null,
                'title' => $object?->name ?? ($ref['title'] ?? null),
                'object' => $object,
            ];
        }, $refs);
    }

    /**
     * Resolve and return only the hydrated models (drops broken references).
     */
    public function resolveObjects($value): Collection
    {
        return collect($this->resolve($value))
            ->pluck('object')
            ->filter()
            ->values();
    }

    /**
     * @return array<int, array{type: string, id: int, infoblock_id?: int, title?: ?string}>
     */
    protected function normalizeRefs($value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        $refs = [];

        foreach ($value as $item) {
            if (! is_array($item) || ! isset($item['type'], $item['id'])) {
                continue;
            }

            if (! in_array($item['type'], ['product', 'catalog', 'infoblock'], true)) {
                continue;
            }

            $ref = [
                'type' => $item['type'],
                'id' => (int) $item['id'],
                'title' => $item['title'] ?? null,
            ];

            if (isset($item['infoblock_id'])) {
                $ref['infoblock_id'] = (int) $item['infoblock_id'];
            }

            $refs[] = $ref;
        }

        return $refs;
    }

    protected function loadProducts(Collection $refs)
    {
        if ($refs->isEmpty()) {
            return [];
        }

        return TProduct::whereIn('id', $refs->pluck('id')->unique())->get()->keyBy('id');
    }

    protected function loadCatalogs(Collection $refs)
    {
        if ($refs->isEmpty()) {
            return [];
        }

        return TCatalog::whereIn('id', $refs->pluck('id')->unique())->get()->keyBy('id');
    }

    protected function loadInfoBlockElements(Collection $refs)
    {
        $elementClass = 'HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlockElement';

        if ($refs->isEmpty() || ! class_exists($elementClass) || ! Schema::hasTable('t_info_block_elements')) {
            return [];
        }

        return $elementClass::whereIn('id', $refs->pluck('id')->unique())->get()->keyBy('id');
    }
}
