<?php

namespace HolartWeb\AxoraCMS\Services\Integration;

use HolartWeb\AxoraCMS\Models\Shop\TCatalog;
use HolartWeb\AxoraCMS\Models\Shop\TProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommerceMLSyncService
{
    /**
     * Синхронизация данных с базой данных
     */
    public function syncData(array $data): array
    {
        $stats = [
            'groups_created' => 0,
            'groups_updated' => 0,
            'products_created' => 0,
            'products_updated' => 0,
        ];

        DB::beginTransaction();

        try {
            // 1. Синхронизируем группы/категории
            if (!empty($data['groups'])) {
                $groupStats = $this->syncGroups($data['groups']);
                $stats['groups_created'] = $groupStats['created'];
                $stats['groups_updated'] = $groupStats['updated'];
            }

            // 2. Синхронизируем товары
            if (!empty($data['products'])) {
                $productStats = $this->syncProducts($data['products']);
                $stats['products_created'] = $productStats['created'];
                $stats['products_updated'] = $productStats['updated'];
            }

            // 3. Обновляем цены и остатки из предложений
            if (!empty($data['offers'])) {
                $this->syncOffers($data['offers']);
            }

            DB::commit();

            Log::info('[CommerceML Sync] Синхронизация завершена', $stats);

            return $stats;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[CommerceML Sync] Ошибка синхронизации', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Синхронизация групп/категорий
     */
    private function syncGroups(array $groups): array
    {
        $created = 0;
        $updated = 0;

        // Сначала создаем/обновляем все группы без привязки к родителям
        foreach ($groups as $groupData) {
            $catalog = TCatalog::where('1c_id', $groupData['id'])->first();

            if ($catalog) {
                // Обновляем существующую категорию
                $catalog->update([
                    'name' => $groupData['name'],
                    'slug' => $this->generateUniqueSlug($groupData['name'], $catalog->id),
                    'is_active' => true,
                ]);
                $updated++;
            } else {
                // Создаем новую категорию через DB::table чтобы обойти fillable
                DB::table('t_catalogs')->insert([
                    '1c_id' => $groupData['id'],
                    'name' => $groupData['name'],
                    'slug' => $this->generateUniqueSlug($groupData['name']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created++;
            }
        }

        // Затем устанавливаем связи родитель-потомок
        foreach ($groups as $groupData) {
            if ($groupData['parent_id']) {
                $catalog = TCatalog::where('1c_id', $groupData['id'])->first();
                $parent = TCatalog::where('1c_id', $groupData['parent_id'])->first();

                if ($catalog && $parent) {
                    $catalog->update(['parent_id' => $parent->id]);
                }
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Синхронизация товаров
     */
    private function syncProducts(array $products): array
    {
        $created = 0;
        $updated = 0;

        foreach ($products as $productData) {
            // Формируем SKU заранее для поиска
            $sku = $productData['article'] ?? $productData['code'] ?? null;

            // Проверяем артикул из свойств (он приоритетнее)
            if (!empty($productData['properties']['Артикул'])) {
                $sku = $productData['properties']['Артикул'];
            }

            // Если SKU пустой, используем 1c_id
            if (empty($sku)) {
                $sku = $productData['id'];
            }

            // Ищем товар по 1c_id или по SKU
            $product = TProduct::where('1c_id', $productData['id'])
                ->orWhere('sku', $sku)
                ->first();

            // Определяем категорию по группе
            $catalogId = null;
            if (!empty($productData['groups'])) {
                $firstGroupId = $productData['groups'][0];
                $catalog = TCatalog::where('1c_id', $firstGroupId)->first();
                if ($catalog) {
                    $catalogId = $catalog->id;
                }
            }

            // Если категория не найдена, создаем/получаем дефолтную
            if (!$catalogId) {
                $defaultCatalog = TCatalog::where('1c_id', 'default-catalog')->first();

                if (!$defaultCatalog) {
                    // Создаем дефолтную категорию через DB::table
                    DB::table('t_catalogs')->insert([
                        '1c_id' => 'default-catalog',
                        'name' => 'Без категории',
                        'slug' => 'bez-kategorii',
                        'is_active' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $defaultCatalog = TCatalog::where('1c_id', 'default-catalog')->first();
                }

                $catalogId = $defaultCatalog->id;
            }

            $data = [
                '1c_id' => $productData['id'],
                'name' => $productData['name'],
                'slug' => $product ? $product->slug : $this->generateUniqueProductSlug($productData['name']),
                'sku' => $sku,
                'is_active' => true,
                'catalog_id' => $catalogId,
            ];

            // Описание товара
            if (!empty($productData['description'])) {
                $data['content'] = $productData['description'];
            }

            // Дополнительная информация из свойств
            if (!empty($productData['properties'])) {
                $additionInfo = [];

                // Штрихкод
                if (isset($productData['properties']['Штрихкод'])) {
                    $additionInfo['barcode'] = $productData['properties']['Штрихкод'];
                }

                // Полное наименование
                if (isset($productData['properties']['Полное наименование'])) {
                    $data['title'] = $productData['properties']['Полное наименование'];
                }

                // Сохраняем все свойства
                $additionInfo['1c_properties'] = $productData['properties'];

                $data['addition_info'] = $additionInfo;
            }

            // Картинки
            if (!empty($productData['images'])) {
                $images = [];
                foreach ($productData['images'] as $index => $imagePath) {
                    // Первое изображение - главное, остальные - галерея
                    $isMainImage = ($index === 0);
                    $laravelPath = $this->convertImagePath($imagePath, $isMainImage);
                    if ($laravelPath) {
                        $images[] = $laravelPath;
                    }
                }

                if (!empty($images)) {
                    $data['main_image'] = $images[0];
                    $data['gallery'] = array_slice($images, 1);
                }
            }

            // Создаем или обновляем товар
            if ($product) {
                $product->update($data);
                $updated++;
            } else {
                // Создаем новый товар через DB::table чтобы обойти fillable
                // Конвертируем массивы в JSON для полей addition_info и gallery
                $insertData = $data;
                if (isset($insertData['addition_info']) && is_array($insertData['addition_info'])) {
                    $insertData['addition_info'] = json_encode($insertData['addition_info']);
                }
                if (isset($insertData['gallery']) && is_array($insertData['gallery'])) {
                    $insertData['gallery'] = json_encode($insertData['gallery']);
                }

                DB::table('t_products')->insert($insertData + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Синхронизация предложений (цены, остатки)
     */
    private function syncOffers(array $offers): void
    {
        if (empty($offers)) {
            return;
        }

        // Получаем все 1c_id для поиска товаров
        $productIds = array_column($offers, 'product_id');

        // Загружаем все товары одним запросом
        $products = TProduct::whereIn('1c_id', $productIds)
            ->get()
            ->keyBy('1c_id');

        Log::info('[CommerceML Sync] Обновление предложений', [
            'total_offers' => count($offers),
            'found_products' => $products->count(),
        ]);

        // Обновляем товары порциями
        $chunkSize = 100;
        $chunks = array_chunk($offers, $chunkSize);

        foreach ($chunks as $chunkIndex => $chunk) {
            foreach ($chunk as $offerData) {
                $product = $products->get($offerData['product_id']);

                if (!$product) {
                    continue;
                }

                $updateData = [];

                // Обновляем цену
                if (!empty($offerData['prices'])) {
                    foreach ($offerData['prices'] as $price) {
                        $updateData['price'] = $price['value'];
                        break;
                    }
                }

                // Обновляем остаток
                if (isset($offerData['quantity'])) {
                    $updateData['quantity'] = (int) $offerData['quantity'];
                }

                if (!empty($updateData)) {
                    // Используем DB::table для обновления чтобы обойти fillable
                    DB::table('t_products')
                        ->where('id', $product->id)
                        ->update($updateData + ['updated_at' => now()]);
                }
            }

            // Логируем прогресс
            if (($chunkIndex + 1) % 10 === 0) {
                Log::info('[CommerceML Sync] Прогресс обновления предложений', [
                    'processed' => ($chunkIndex + 1) * $chunkSize,
                    'total' => count($offers),
                ]);
            }
        }
    }

    /**
     * Конвертация пути изображения из 1С в Laravel
     *
     * @param string $path1c Путь из 1С
     * @param bool $isMainImage true - главное изображение, false - галерея
     */
    private function convertImagePath(string $path1c, bool $isMainImage = true): ?string
    {
        // Извлекаем только имя файла
        $filename = basename($path1c);

        // Путь к исходному файлу
        $sourcePath = "exchange/images/{$filename}";

        // Проверяем существование файла
        if (!Storage::disk('public')->exists($sourcePath)) {
            Log::warning('[CommerceML Sync] Изображение не найдено', [
                'source' => $sourcePath,
                '1c_path' => $path1c,
            ]);
            return null;
        }

        // Определяем целевую папку
        if ($isMainImage) {
            $destinationPath = "images/{$filename}";
            $dbPath = "/images/{$filename}";
        } else {
            $destinationPath = "products/gallery/{$filename}";
            $dbPath = "/products/gallery/{$filename}";
        }

        try {
            // Копируем файл в нужную папку (если его там еще нет)
            if (!Storage::disk('public')->exists($destinationPath)) {
                $content = Storage::disk('public')->get($sourcePath);
                Storage::disk('public')->put($destinationPath, $content);

                Log::info('[CommerceML Sync] Изображение скопировано', [
                    'from' => $sourcePath,
                    'to' => $destinationPath,
                    'db_path' => $dbPath,
                ]);
            }

            // Возвращаем путь для БД
            return $dbPath;

        } catch (\Exception $e) {
            Log::error('[CommerceML Sync] Ошибка копирования изображения', [
                'source' => $sourcePath,
                'destination' => $destinationPath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Генерация уникального slug для категории
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        // Проверяем существование slug
        while (true) {
            $query = TCatalog::where('slug', $slug);

            // Исключаем текущую категорию при обновлении
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            // Если slug занят, добавляем счетчик
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Генерация уникального slug для товара
     */
    private function generateUniqueProductSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        // Проверяем существование slug
        while (true) {
            $query = TProduct::where('slug', $slug);

            // Исключаем текущий товар при обновлении
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            // Если slug занят, добавляем счетчик
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
