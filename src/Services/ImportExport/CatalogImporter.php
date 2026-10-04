<?php

namespace HolartWeb\AxoraCMS\Services\ImportExport;

use HolartWeb\AxoraCMS\Models\Shop\TCatalog;
use HolartWeb\AxoraCMS\Models\Shop\TCatalogProperty;
use HolartWeb\AxoraCMS\Models\Shop\TCharacteristicDefinition;
use HolartWeb\AxoraCMS\Models\Shop\TProduct;
use HolartWeb\AxoraCMS\Models\Shop\TProductPropertyValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Imports catalog rows (products) using a column → field mapping.
 *
 * Mapping targets:
 *   - product fields: "name", "sku", "price", ... (see productFields());
 *   - "category" — category id, name or path "Parent / Child";
 *   - "property:{code}" — category property (resolved in the product's category and its parents);
 *   - "characteristic:{code}" — product characteristic (stored in addition_info).
 *
 * Options:
 *   - match_by: "sku" | "name" — how an existing product is found;
 *   - mode: "create_update" | "create_only" | "update_only";
 *   - create_categories: create categories that do not exist yet;
 *   - default_catalog_id: category for rows without one;
 *   - download_images: download image URLs into the storage.
 */
class CatalogImporter
{
    /**
     * Separators of multiple values in one cell.
     */
    protected const MULTI_SEPARATORS = '/\s*[;|]\s*/u';

    /** @var array<int, Collection> */
    protected array $catalogProperties = [];

    /** @var array<string, int> */
    protected array $catalogIdsByPath = [];

    /** @var Collection|null */
    protected $characteristics = null;

    /**
     * Product fields available for mapping.
     *
     * @return array<int, array{key: string, label: string, hint?: string}>
     */
    public function productFields(): array
    {
        $fields = [
            ['key' => 'name', 'label' => 'Название', 'hint' => 'Обязательно для новых товаров'],
            ['key' => 'sku', 'label' => 'Артикул (SKU)'],
            ['key' => 'category', 'label' => 'Категория', 'hint' => 'ID, название или путь «Родитель / Дочерняя»'],
            ['key' => 'price', 'label' => 'Цена'],
            ['key' => 'old_price', 'label' => 'Старая цена'],
            ['key' => 'description', 'label' => 'Краткое описание'],
            ['key' => 'content', 'label' => 'Контент (HTML)'],
            ['key' => 'slug', 'label' => 'URL (slug)'],
            ['key' => 'title', 'label' => 'SEO: заголовок'],
            ['key' => 'keywords', 'label' => 'SEO: ключевые слова'],
            ['key' => 'main_image', 'label' => 'Главное изображение', 'hint' => 'URL или путь к файлу'],
            ['key' => 'gallery', 'label' => 'Галерея', 'hint' => 'Несколько URL через «;» или «,»'],
            ['key' => 'is_active', 'label' => 'Активен', 'hint' => '1 / 0, да / нет'],
            ['key' => 'is_new', 'label' => 'Новинка'],
            ['key' => 'is_hot', 'label' => 'Хит'],
            ['key' => 'is_recommended', 'label' => 'Рекомендуемый'],
        ];

        if (Schema::hasColumn('t_products', 'quantity')) {
            $fields[] = ['key' => 'quantity', 'label' => 'Остаток'];
        }

        return $fields;
    }

    /**
     * All mapping targets grouped for the UI.
     *
     * @return array<int, array{group: string, items: array<int, array{key: string, label: string, hint?: string}>}>
     */
    public function targets(): array
    {
        $groups = [['group' => 'Товар', 'items' => $this->productFields()]];

        $properties = TCatalogProperty::query()
            ->orderBy('name')
            ->get(['code', 'name'])
            ->unique('code')
            ->map(fn ($property) => ['key' => 'property:'.$property->code, 'label' => $property->name, 'hint' => $property->code])
            ->values()
            ->all();
        if ($properties) {
            $groups[] = ['group' => 'Свойства категорий', 'items' => $properties];
        }

        if (Schema::hasTable('t_characteristic_definitions')) {
            $characteristics = TCharacteristicDefinition::whereIn('applies_to', ['product', 'both'])
                ->orderBy('sort_order')
                ->get(['code', 'name'])
                ->map(fn ($definition) => ['key' => 'characteristic:'.$definition->code, 'label' => $definition->name, 'hint' => $definition->code])
                ->all();
            if ($characteristics) {
                $groups[] = ['group' => 'Характеристики', 'items' => $characteristics];
            }
        }

        return $groups;
    }

    /**
     * Import one row.
     *
     * @param  array<string, string>  $row  Column key => value.
     * @param  array<string, string>  $mapping  Column key => target.
     * @param  array<string, mixed>  $options
     * @return string "created" | "updated" | "skipped"
     *
     * @throws ImportRowException With a human-readable message.
     */
    public function importRow(array $row, array $mapping, array $options): string
    {
        $values = [];
        foreach ($mapping as $column => $target) {
            if ($target && array_key_exists($column, $row)) {
                $values[$target] = trim((string) $row[$column]);
            }
        }

        $product = $this->findExisting($values, $options['match_by'] ?? 'sku');
        $mode = $options['mode'] ?? 'create_update';

        if (($product && $mode === 'create_only') || (! $product && $mode === 'update_only')) {
            return 'skipped';
        }

        $attributes = $this->productAttributes($values, $options, $product);
        $isNew = ! $product;

        if ($isNew) {
            if (empty($attributes['name'])) {
                throw new ImportRowException('не заполнено название товара');
            }
            if (empty($attributes['catalog_id'])) {
                throw new ImportRowException('не указана категория');
            }
            $attributes['slug'] ??= TProduct::generateSlug($attributes['name']);
            $attributes['sku'] ??= $attributes['slug'];
            $attributes['price'] ??= 0;
            $attributes['is_active'] ??= true;

            $product = TProduct::create($attributes);
        } elseif ($attributes) {
            $product->update($attributes);
        }

        $this->saveCharacteristics($product, $values);
        $this->saveProperties($product, $values);

        return $isNew ? 'created' : 'updated';
    }

    /**
     * @param  array<string, string>  $values
     */
    protected function findExisting(array $values, string $matchBy): ?TProduct
    {
        $key = $matchBy === 'name' ? 'name' : 'sku';
        $value = $values[$key] ?? '';

        return $value === '' ? null : TProduct::where($key, $value)->orderBy('id')->first();
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function productAttributes(array $values, array $options, ?TProduct $product): array
    {
        $attributes = [];

        foreach (['name', 'sku', 'description', 'content', 'title', 'keywords'] as $field) {
            if (isset($values[$field]) && $values[$field] !== '') {
                $attributes[$field] = $values[$field];
            }
        }

        if (! empty($values['slug'])) {
            $slug = Str::slug($values['slug']);
            if ($slug !== '' && ! TProduct::where('slug', $slug)->when($product, fn ($q) => $q->whereKeyNot($product->id))->exists()) {
                $attributes['slug'] = $slug;
            }
        }

        foreach (['price', 'old_price'] as $field) {
            if (isset($values[$field]) && $values[$field] !== '') {
                $number = $this->number($values[$field]);
                if ($number === null) {
                    throw new ImportRowException("некорректное число в поле «{$field}»: {$values[$field]}");
                }
                $attributes[$field] = $number;
            }
        }

        if (isset($values['quantity']) && $values['quantity'] !== '') {
            $attributes['quantity'] = (int) $this->number($values['quantity']);
        }

        foreach (['is_active', 'is_new', 'is_hot', 'is_recommended'] as $field) {
            if (isset($values[$field]) && $values[$field] !== '') {
                $attributes[$field] = $this->boolean($values[$field]);
            }
        }

        // Several categories in one cell ("A / B; C"): the product goes to the first one.
        $category = trim(explode(';', $values['category'] ?? '')[0]);
        if ($category !== '') {
            $attributes['catalog_id'] = $this->resolveCatalog($category, (bool) ($options['create_categories'] ?? false));
        } elseif (! $product && ! empty($options['default_catalog_id'])) {
            $attributes['catalog_id'] = (int) $options['default_catalog_id'];
        }

        $download = (bool) ($options['download_images'] ?? true);
        if (! empty($values['main_image'])) {
            // Several pictures in one cell: the first one is the main image.
            $first = preg_split('/\s*;\s*/u', $values['main_image'], -1, PREG_SPLIT_NO_EMPTY)[0] ?? '';
            $attributes['main_image'] = $this->image($first, $download);
        }
        if (! empty($values['gallery'])) {
            $images = preg_split('/\s*[;,\n]\s*/u', $values['gallery'], -1, PREG_SPLIT_NO_EMPTY);
            $attributes['gallery'] = array_values(array_filter(array_map(fn ($url) => $this->image($url, $download), $images)));
        }

        return $attributes;
    }

    /**
     * Category id by id, name or path "A / B"; optionally creates missing ones.
     */
    protected function resolveCatalog(string $value, bool $create): int
    {
        if (ctype_digit($value) && TCatalog::whereKey((int) $value)->exists()) {
            return (int) $value;
        }

        $cacheKey = mb_strtolower($value);
        if (isset($this->catalogIdsByPath[$cacheKey])) {
            return $this->catalogIdsByPath[$cacheKey];
        }

        $parts = preg_split('/\s*[\/>\\\\]\s*/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        $parentId = null;
        $catalog = null;

        foreach ($parts as $index => $name) {
            $query = TCatalog::where('name', $name);
            // A single name matches anywhere in the tree; a path is followed level by level.
            if (count($parts) > 1 || $index > 0) {
                $query->where('parent_id', $parentId);
            }
            $catalog = $query->orderBy('id')->first();

            if (! $catalog) {
                if (! $create) {
                    throw new ImportRowException("категория «{$value}» не найдена");
                }
                $catalog = TCatalog::create([
                    'name' => $name,
                    'parent_id' => $parentId,
                    'slug' => $this->uniqueCatalogSlug($name),
                    'is_active' => true,
                ]);
            }
            $parentId = $catalog->id;
        }

        return $this->catalogIdsByPath[$cacheKey] = $catalog->id;
    }

    protected function uniqueCatalogSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $n = 1;
        while (TCatalog::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$n);
        }

        return $slug;
    }

    /**
     * @param  array<string, string>  $values
     */
    protected function saveCharacteristics(TProduct $product, array $values): void
    {
        $info = is_array($product->addition_info) ? $product->addition_info : [];
        $changed = false;

        foreach ($values as $target => $value) {
            if (! str_starts_with($target, 'characteristic:') || $value === '') {
                continue;
            }
            $code = substr($target, 15);
            $definition = $this->characteristicDefinitions()->get($code);
            $info[$code] = $definition && $definition->multiple ? $this->splitMultiple($value) : $value;
            $changed = true;
        }

        if ($changed) {
            $product->forceFill(['addition_info' => $info])->save();
        }
    }

    /**
     * @param  array<string, string>  $values
     */
    protected function saveProperties(TProduct $product, array $values): void
    {
        $propertyTargets = array_filter($values, fn ($value, $target) => str_starts_with($target, 'property:') && $value !== '', ARRAY_FILTER_USE_BOTH);
        if (! $propertyTargets || ! $product->catalog_id) {
            return;
        }

        $properties = $this->propertiesOfCatalog($product->catalog_id);

        foreach ($propertyTargets as $target => $value) {
            $property = $properties->get(substr($target, 9));
            if (! $property) {
                continue; // The property does not exist in this product's category.
            }

            $stored = $property->is_multiple
                ? json_encode($this->splitMultiple($value), JSON_UNESCAPED_UNICODE)
                : $value;

            TProductPropertyValue::updateOrCreate(
                ['product_id' => $product->id, 'property_id' => $property->id],
                ['value' => $stored]
            );
        }
    }

    /**
     * Properties of a category including inherited ones, keyed by code.
     */
    protected function propertiesOfCatalog(int $catalogId)
    {
        return $this->catalogProperties[$catalogId] ??= (TCatalog::find($catalogId)?->getAllProperties() ?? collect())->keyBy('code');
    }

    protected function characteristicDefinitions()
    {
        return $this->characteristics ??= Schema::hasTable('t_characteristic_definitions')
            ? TCharacteristicDefinition::get()->keyBy('code')
            : collect();
    }

    /**
     * @return array<int, string>
     */
    protected function splitMultiple(string $value): array
    {
        return array_values(array_filter(preg_split(self::MULTI_SEPARATORS, $value) ?: [], fn ($v) => $v !== ''));
    }

    protected function number(string $value): ?float
    {
        $normalized = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], $value);
        $normalized = preg_replace('/[^\d.\-]/', '', $normalized);

        return is_numeric($normalized) ? round((float) $normalized, 2) : null;
    }

    protected function boolean(string $value): bool
    {
        return in_array(mb_strtolower(trim($value)), ['1', 'да', 'yes', 'y', 'true', '+', 'on', 'активен', 'active'], true);
    }

    /**
     * Make a URL fetchable: punycode for Cyrillic domains (e.g. "сайт.рф"),
     * encoded spaces and non-ASCII characters in the path.
     *
     * parse_url() is not multibyte-safe and mangles such hosts, so the URL is
     * split with a Unicode-aware regular expression instead.
     */
    protected function normalizeUrl(string $url): string
    {
        if (! preg_match('#^(https?)://([^/:?\#]+)(:\d+)?([^?\#]*)(\?[^\#]*)?#iu', trim($url), $parts)) {
            return $url;
        }

        [, $scheme, $host] = $parts;
        $port = $parts[3] ?? '';
        $path = $parts[4] ?? '';
        $query = $parts[5] ?? '';

        if (preg_match('/[^\x20-\x7e]/', $host) && function_exists('idn_to_ascii')) {
            $host = idn_to_ascii(mb_strtolower($host), IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $host;
        }

        $path = implode('/', array_map(fn ($segment) => rawurlencode(rawurldecode($segment)), explode('/', $path)));
        $query = preg_replace_callback('/[^\x21-\x7e]/u', fn ($match) => rawurlencode($match[0]), $query);

        return strtolower($scheme).'://'.$host.$port.$path.$query;
    }

    /**
     * Stored path of an image: downloads http(s) URLs when allowed, keeps paths as is.
     */
    protected function image(string $value, bool $download): ?string
    {
        $value = trim($value);
        if (! preg_match('#^https?://#i', $value)) {
            return $value !== '' ? $value : null;
        }
        if (! $download) {
            return $value;
        }

        try {
            $response = Http::timeout(15)->get($this->normalizeUrl($value));
            $type = (string) $response->header('Content-Type');
            $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][strtolower(trim(explode(';', $type)[0]))] ?? null;

            if (! $response->successful() || ! $extension) {
                throw new ImportRowException("не удалось загрузить изображение {$value}");
            }

            $path = 'products/import/'.date('Y/m').'/'.Str::random(20).'.'.$extension;
            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (ImportRowException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ImportRowException("не удалось загрузить изображение {$value}");
        }
    }
}
