<?php

namespace HolartWeb\AxoraCMS\Services;

use HolartWeb\AxoraCMS\Models\Shop\TCatalog;
use HolartWeb\AxoraCMS\Models\Shop\TProduct;
use HolartWeb\AxoraCMS\Models\Shop\TProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bulk price changes ("Менеджер цен"): raise or lower the prices of selected
 * products and/or whole categories by a percentage or a fixed amount.
 *
 * Options:
 *   - product_ids / catalog_ids — what to change (combined);
 *   - include_subcategories — also take products of nested categories;
 *   - direction — "increase" | "decrease";
 *   - mode — "percent" | "fixed";
 *   - value — positive number (percent or currency amount);
 *   - apply_to_variants — change variant prices too;
 *   - rounding — "none" | "1" | "10" | "100" (round the result to that step);
 *   - old_price — "keep" | "previous" (current price becomes the old one) | "clear".
 */
class PriceManagerService
{
    /**
     * Rows returned by preview().
     */
    public const PREVIEW_LIMIT = 100;

    /**
     * Ids of the products the change applies to.
     *
     * @param  array<string, mixed>  $options
     * @return Collection<int, int>
     */
    public function resolveProductIds(array $options): Collection
    {
        $productIds = collect($options['product_ids'] ?? [])->map(fn ($id) => (int) $id);
        $catalogIds = collect($options['catalog_ids'] ?? [])->map(fn ($id) => (int) $id)->filter();

        if ($catalogIds->isNotEmpty()) {
            if ($options['include_subcategories'] ?? true) {
                $catalogIds = $this->withDescendants($catalogIds);
            }

            $productIds = $productIds->merge(
                TProduct::whereIn('catalog_id', $catalogIds)->pluck('id')
            );
        }

        return $productIds->unique()->values();
    }

    /**
     * New price for the given one.
     *
     * @param  array<string, mixed>  $options
     */
    public function calculate(float $price, array $options): float
    {
        $value = (float) $options['value'];
        $delta = $options['mode'] === 'percent' ? $price * $value / 100 : $value;
        $result = $options['direction'] === 'decrease' ? $price - $delta : $price + $delta;

        $step = (float) ($options['rounding'] ?? 0);
        if ($step > 0) {
            $result = round($result / $step) * $step;
        }

        return max(0, round($result, 2));
    }

    /**
     * Totals and the first rows of the change, without saving anything.
     *
     * @param  array<string, mixed>  $options
     * @return array{products: int, variants: int, rows: array<int, array<string, mixed>>}
     */
    public function preview(array $options): array
    {
        $productIds = $this->resolveProductIds($options);
        $withVariants = (bool) ($options['apply_to_variants'] ?? true);

        $rows = TProduct::whereIn('id', $productIds->take(self::PREVIEW_LIMIT))
            ->with(['catalog:id,name', 'variants:id,product_id,name,sku,price'])
            ->orderBy('name')
            ->get()
            ->map(fn (TProduct $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'catalog' => $product->catalog?->name,
                'price' => (float) $product->price,
                'new_price' => $this->calculate((float) $product->price, $options),
                'variants' => $withVariants
                    ? $product->variants->map(fn (TProductVariant $variant) => [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'sku' => $variant->sku,
                        'price' => (float) $variant->price,
                        'new_price' => $this->calculate((float) $variant->price, $options),
                    ])->values()->all()
                    : [],
            ])
            ->all();

        return [
            'products' => $productIds->count(),
            'variants' => $withVariants ? TProductVariant::whereIn('product_id', $productIds)->count() : 0,
            'rows' => $rows,
        ];
    }

    /**
     * Apply the change.
     *
     * @param  array<string, mixed>  $options
     * @return array{products: int, variants: int}
     */
    public function apply(array $options): array
    {
        $productIds = $this->resolveProductIds($options);
        $withVariants = (bool) ($options['apply_to_variants'] ?? true);
        $counts = ['products' => 0, 'variants' => 0];

        DB::transaction(function () use ($productIds, $withVariants, $options, &$counts) {
            foreach ($productIds->chunk(500) as $chunk) {
                TProduct::whereIn('id', $chunk)->get(['id', 'price', 'old_price'])
                    ->each(function (TProduct $product) use ($options, &$counts) {
                        $this->updatePrice($product, $options);
                        $counts['products']++;
                    });

                if ($withVariants) {
                    TProductVariant::whereIn('product_id', $chunk)->get(['id', 'price', 'old_price'])
                        ->each(function (TProductVariant $variant) use ($options, &$counts) {
                            $this->updatePrice($variant, $options);
                            $counts['variants']++;
                        });
                }
            }
        });

        return $counts;
    }

    /**
     * Write the new price (and the old price, if requested) without touching
     * anything else on the model.
     *
     * @param  array<string, mixed>  $options
     */
    protected function updatePrice(TProduct|TProductVariant $model, array $options): void
    {
        $current = (float) $model->price;
        $attributes = ['price' => $this->calculate($current, $options)];

        $attributes['old_price'] = match ($options['old_price'] ?? 'keep') {
            'previous' => $current,
            'clear' => null,
            default => $model->old_price,
        };

        $model->newQuery()->whereKey($model->getKey())->update($attributes);
    }

    /**
     * The given categories plus all their nested categories.
     *
     * @param  Collection<int, int>  $catalogIds
     * @return Collection<int, int>
     */
    protected function withDescendants(Collection $catalogIds): Collection
    {
        $childrenByParent = TCatalog::get(['id', 'parent_id'])->groupBy('parent_id');
        $result = $catalogIds->all();
        $queue = $catalogIds->all();

        while ($queue) {
            $parentId = array_shift($queue);
            foreach ($childrenByParent->get($parentId, collect()) as $child) {
                if (! in_array($child->id, $result, true)) {
                    $result[] = $child->id;
                    $queue[] = $child->id;
                }
            }
        }

        return collect($result);
    }
}
