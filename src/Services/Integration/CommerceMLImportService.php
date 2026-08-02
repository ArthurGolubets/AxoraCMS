<?php

namespace HolartWeb\AxoraCMS\Services\Integration;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class CommerceMLImportService
{
    /**
     * Импорт всех XML файлов из папки (включая объединение частей)
     */
    public function importFromFolder(string $folderPath): array
    {
        Log::info('[CommerceML] Импорт из папки', ['folder' => $folderPath]);

        $files = Storage::disk('public')->files($folderPath);
        $xmlFiles = array_filter($files, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'xml';
        });

        if (empty($xmlFiles)) {
            throw new \Exception("XML файлы не найдены в папке: {$folderPath}");
        }

        // Сортируем файлы по имени (по timestamp)
        sort($xmlFiles);

        // Пробуем собрать полный XML из частей
        $completeXml = $this->mergeXmlParts($xmlFiles);

        if ($completeXml) {
            Log::info('[CommerceML] XML собран из частей', ['parts' => count($xmlFiles)]);
            return $this->parseXmlContent($completeXml);
        }

        // Если не получилось собрать, пробуем импортировать каждый файл отдельно
        $result = [
            'groups' => [],
            'products' => [],
            'offers' => [],
        ];

        foreach ($xmlFiles as $filePath) {
            try {
                $fileResult = $this->importFromFile($filePath);
                $result['groups'] = array_merge($result['groups'], $fileResult['groups']);
                $result['products'] = array_merge($result['products'], $fileResult['products']);
                $result['offers'] = array_merge($result['offers'], $fileResult['offers']);
            } catch (\Exception $e) {
                Log::warning('[CommerceML] Не удалось импортировать файл', [
                    'file' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * Объединение частей XML в один полный файл
     */
    private function mergeXmlParts(array $files): ?string
    {
        $parts = [];
        $hasStart = false;
        $hasEnd = false;

        foreach ($files as $file) {
            $content = Storage::disk('public')->get($file);
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // Удаляем BOM

            $parts[] = [
                'file' => basename($file),
                'content' => $content,
                'has_xml_header' => preg_match('/^<\?xml/', $content),
                'has_end_tag' => preg_match('/<\/КоммерческаяИнформация>\s*$/s', $content),
            ];

            if (preg_match('/^<\?xml/', $content)) {
                $hasStart = true;
            }
            if (preg_match('/<\/КоммерческаяИнформация>\s*$/s', $content)) {
                $hasEnd = true;
            }
        }

        // Если нашли начало и конец, склеиваем все части
        if ($hasStart && $hasEnd) {
            Log::info('[CommerceML] Объединение частей XML', [
                'parts' => array_map(function($p) {
                    return [
                        'file' => $p['file'],
                        'has_header' => $p['has_xml_header'],
                        'has_end' => $p['has_end_tag'],
                    ];
                }, $parts)
            ]);

            // Склеиваем содержимое, но из средних частей удаляем BOM и возможные артефакты
            $result = '';
            foreach ($parts as $index => $part) {
                $content = $part['content'];

                // Из средних частей (не первой) удаляем возможные BOM и пробелы в начале
                if ($index > 0) {
                    $content = ltrim($content);
                }

                $result .= $content;
            }

            return $result;
        }

        return null;
    }

    /**
     * Импорт товаров из XML файла CommerceML
     */
    public function importFromFile(string $filePath): array
    {
        Log::info('[CommerceML] Начало импорта', ['file' => $filePath]);

        if (!Storage::disk('public')->exists($filePath)) {
            Log::error('[CommerceML] Файл не найден', ['file' => $filePath]);
            throw new \Exception("Файл не найден: {$filePath}");
        }

        $xmlContent = Storage::disk('public')->get($filePath);
        return $this->parseXmlContent($xmlContent);
    }

    /**
     * Парсинг XML контента
     */
    private function parseXmlContent(string $xmlContent): array
    {
        try {
            // Удаляем BOM если есть
            $xmlContent = preg_replace('/^\xEF\xBB\xBF/', '', $xmlContent);

            // Загружаем XML с опциями для больших файлов
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_COMPACT);

            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                $errorMsg = !empty($errors) ? $errors[0]->message : 'Unknown XML error';
                throw new \Exception("XML parsing error: {$errorMsg}");
            }

            // Регистрация namespace для CommerceML
            $xml->registerXPathNamespace('ns', 'urn:1C.ru:commerceml_210');

            $result = [
                'groups' => [],
                'products' => [],
                'offers' => [],
            ];

            // Парсим классификатор (категории/группы)
            if (isset($xml->Классификатор)) {
                $result['groups'] = $this->parseGroups($xml->Классификатор);
            }

            // Парсим каталог товаров
            if (isset($xml->Каталог)) {
                $result['products'] = $this->parseProducts($xml->Каталог);
            }

            // Парсим пакет предложений (цены, остатки)
            if (isset($xml->ПакетПредложений)) {
                $result['offers'] = $this->parseOffers($xml->ПакетПредложений);
            }

            Log::info('[CommerceML] Импорт завершен', [
                'groups' => count($result['groups']),
                'products' => count($result['products']),
                'offers' => count($result['offers']),
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('[CommerceML] Ошибка парсинга XML', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Парсинг групп товаров (категорий)
     */
    private function parseGroups(SimpleXMLElement $classifier): array
    {
        $groups = [];

        if (!isset($classifier->Группы)) {
            return $groups;
        }

        // Рекурсивно парсим все группы и подгруппы
        $groups = $this->parseGroupsRecursive($classifier->Группы->Группа);

        return $groups;
    }

    /**
     * Рекурсивный парсинг групп и подгрупп
     */
    private function parseGroupsRecursive($groupElements, ?string $parentId = null): array
    {
        $groups = [];

        foreach ($groupElements as $group) {
            $groupId = (string) $group->Ид;
            $groupName = (string) $group->Наименование;

            // Определяем родителя: либо из элемента <Родитель>, либо из параметра
            $groupParentId = null;
            if (isset($group->Родитель)) {
                $groupParentId = (string) $group->Родитель;
            } elseif ($parentId !== null) {
                $groupParentId = $parentId;
            }

            // Добавляем текущую группу
            $groups[] = [
                'id' => $groupId,
                'name' => $groupName,
                'parent_id' => $groupParentId,
            ];

            // Если у группы есть вложенные подгруппы, парсим их рекурсивно
            if (isset($group->Группы) && isset($group->Группы->Группа)) {
                $subGroups = $this->parseGroupsRecursive($group->Группы->Группа, $groupId);
                $groups = array_merge($groups, $subGroups);
            }
        }

        return $groups;
    }

    /**
     * Парсинг товаров
     */
    private function parseProducts(SimpleXMLElement $catalog): array
    {
        $products = [];

        if (!isset($catalog->Товары)) {
            return $products;
        }

        foreach ($catalog->Товары->Товар as $product) {
            $productData = [
                'id' => (string) $product->Ид,
                'name' => (string) $product->Наименование,
                'article' => isset($product->Артикул) ? (string) $product->Артикул : null,
                'code' => isset($product->Код) ? (string) $product->Код : null,
                'description' => isset($product->Описание) ? (string) $product->Описание : null,
                'category_id' => isset($product->Категория) ? (string) $product->Категория : null,
                'groups' => [],
                'properties' => [],
                'images' => [],
                'tax_rate' => null,
                'unit' => null,
            ];

            // Группы товара
            if (isset($product->Группы)) {
                foreach ($product->Группы->Ид as $groupId) {
                    $productData['groups'][] = (string) $groupId;
                }
            }

            // Единица измерения
            if (isset($product->БазоваяЕдиница)) {
                $productData['unit'] = [
                    'code' => (string) $product->БазоваяЕдиница['Код'],
                    'name' => (string) $product->БазоваяЕдиница['НаименованиеПолное'],
                    'international' => (string) $product->БазоваяЕдиница['МеждународноеСокращение'],
                ];
            }

            // Ставка НДС
            if (isset($product->СтавкиНалогов->СтавкаНалога)) {
                foreach ($product->СтавкиНалогов->СтавкаНалога as $tax) {
                    if ((string) $tax->Наименование === 'НДС') {
                        $productData['tax_rate'] = (float) $tax->Ставка;
                        break;
                    }
                }
            }

            // Реквизиты/свойства товара
            if (isset($product->ЗначенияРеквизитов)) {
                foreach ($product->ЗначенияРеквизитов->ЗначениеРеквизита as $property) {
                    $name = (string) $property->Наименование;
                    $value = (string) $property->Значение;

                    $productData['properties'][$name] = $value;
                }
            }

            // Картинки
            if (isset($product->Картинка)) {
                foreach ($product->Картинка as $image) {
                    $productData['images'][] = (string) $image;
                }
            }

            $products[] = $productData;
        }

        return $products;
    }

    /**
     * Парсинг предложений (цены, остатки)
     */
    private function parseOffers(SimpleXMLElement $offersPackage): array
    {
        $offers = [];

        if (!isset($offersPackage->Предложения)) {
            return $offers;
        }

        foreach ($offersPackage->Предложения->Предложение as $offer) {
            $offerData = [
                'id' => (string) $offer->Ид,
                'product_id' => (string) $offer->Ид,
                'article' => isset($offer->Артикул) ? (string) $offer->Артикул : null,
                'name' => isset($offer->Наименование) ? (string) $offer->Наименование : null,
                'barcode' => isset($offer->Штрихкод) ? (string) $offer->Штрихкод : null,
                'prices' => [],
                'quantity' => 0,
            ];

            // Цены
            if (isset($offer->Цены)) {
                foreach ($offer->Цены->Цена as $price) {
                    $offerData['prices'][] = [
                        'type' => (string) $price->ИдТипаЦены,
                        'value' => (float) $price->ЦенаЗаЕдиницу,
                        'currency' => (string) $price->Валюта,
                    ];
                }
            }

            // Остатки
            if (isset($offer->Остатки)) {
                foreach ($offer->Остатки->Остаток as $stock) {
                    $offerData['quantity'] += (float) $stock->Количество;
                }
            }

            // Количество (альтернативный формат)
            if (isset($offer->Количество)) {
                $offerData['quantity'] = (float) $offer->Количество;
            }

            $offers[] = $offerData;
        }

        return $offers;
    }
}
