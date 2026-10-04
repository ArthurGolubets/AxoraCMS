<?php

namespace HolartWeb\AxoraCMS\Services\ImportExport\Exporters;

use HolartWeb\AxoraCMS\Models\Shop\TCatalog;
use HolartWeb\AxoraCMS\Models\Shop\TCatalogProperty;
use HolartWeb\AxoraCMS\Models\Shop\TCharacteristicDefinition;
use HolartWeb\AxoraCMS\Models\Shop\TProduct;
use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog products. Headers match the import field labels, so an exported
 * file can be imported back with automatic column mapping.
 *
 * Options: catalog_ids (with nested categories).
 */
class ProductsExport extends AbstractExportSource
{
    /** @var array<int, string>|null */
    protected ?array $catalogPaths = null;

    public function key(): string
    {
        return 'products';
    }

    public function label(): string
    {
        return 'Каталог товаров';
    }

    public function available(): bool
    {
        return TModule::isInstalled('shop');
    }

    public function title(array $options): string
    {
        return 'Экспорт каталога товаров';
    }

    public function headers(array $options): array
    {
        $headers = ['ID', 'Название', 'Артикул (SKU)', 'Категория', 'Цена', 'Старая цена', 'Краткое описание', 'URL (slug)',
            'Главное изображение', 'Активен', 'Новинка', 'Хит', 'Рекомендуемый'];

        if ($this->hasQuantity()) {
            $headers[] = 'Остаток';
        }

        foreach ($this->propertyColumns() as $property) {
            $headers[] = $property['name'];
        }
        foreach ($this->characteristicColumns() as $definition) {
            $headers[] = $definition['name'];
        }

        return $headers;
    }

    public function total(array $options): int
    {
        return $this->query($options)->count();
    }

    public function rows(array $options, int $offset, int $limit): array
    {
        $properties = $this->propertyColumns();
        $characteristics = $this->characteristicColumns();

        return $this->query($options)
            ->with('propertyValues.property:id,code')
            ->orderBy('id')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function (TProduct $product) use ($properties, $characteristics) {
                $row = [
                    $product->id,
                    $product->name,
                    $product->sku,
                    $this->catalogPath($product->catalog_id),
                    (float) $product->price,
                    $product->old_price !== null ? (float) $product->old_price : null,
                    $product->description,
                    $product->slug,
                    $this->fileUrl($product->main_image),
                    $product->is_active ? 1 : 0,
                    $product->is_new ? 1 : 0,
                    $product->is_hot ? 1 : 0,
                    $product->is_recommended ? 1 : 0,
                ];

                if ($this->hasQuantity()) {
                    $row[] = $product->quantity;
                }

                $values = $product->propertyValues->mapWithKeys(fn ($value) => [$value->property?->code => $value->value]);
                foreach ($properties as $property) {
                    $value = $values->get($property['code']);
                    $decoded = is_string($value) && str_starts_with($value, '[') ? json_decode($value, true) : null;
                    $row[] = is_array($decoded) ? $this->cell($decoded) : $value;
                }

                $info = is_array($product->addition_info) ? $product->addition_info : [];
                foreach ($characteristics as $definition) {
                    $row[] = $this->cell($info[$definition['code']] ?? null);
                }

                return $row;
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function query(array $options)
    {
        $catalogIds = array_filter(array_map('intval', $options['catalog_ids'] ?? []));
        if ($catalogIds) {
            foreach (TCatalog::whereIn('id', $catalogIds)->get() as $catalog) {
                $catalogIds = array_merge($catalogIds, $catalog->getDescendantIds());
            }
        }

        return TProduct::query()->when($catalogIds, fn ($query) => $query->whereIn('catalog_id', array_unique($catalogIds)));
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    protected function propertyColumns(): array
    {
        return TCatalogProperty::orderBy('name')->get(['code', 'name'])->unique('code')
            ->map(fn ($p) => ['code' => $p->code, 'name' => $p->name])->values()->all();
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    protected function characteristicColumns(): array
    {
        if (! Schema::hasTable('t_characteristic_definitions')) {
            return [];
        }

        return TCharacteristicDefinition::whereIn('applies_to', ['product', 'both'])->orderBy('sort_order')->get(['code', 'name'])
            ->map(fn ($d) => ['code' => $d->code, 'name' => $d->name])->all();
    }

    protected function catalogPath(?int $catalogId): string
    {
        if ($this->catalogPaths === null) {
            $catalogs = TCatalog::get(['id', 'parent_id', 'name'])->keyBy('id');
            $this->catalogPaths = [];
            foreach ($catalogs as $catalog) {
                $parts = [];
                $current = $catalog;
                while ($current) {
                    array_unshift($parts, $current->name);
                    $current = $catalogs->get($current->parent_id);
                }
                $this->catalogPaths[$catalog->id] = implode(' / ', $parts);
            }
        }

        return $catalogId ? ($this->catalogPaths[$catalogId] ?? '') : '';
    }

    protected function hasQuantity(): bool
    {
        return Schema::hasColumn('t_products', 'quantity');
    }
}
