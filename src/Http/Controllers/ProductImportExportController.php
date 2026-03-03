<?php

namespace HolartWeb\HolartCMS\Http\Controllers;

use HolartWeb\HolartCMS\Models\TAdminAction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductImportExportController extends Controller
{
    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Название');
        $sheet->setCellValue('C1', 'Slug');
        $sheet->setCellValue('D1', 'SKU');
        $sheet->setCellValue('E1', 'Описание');
        $sheet->setCellValue('F1', 'Цена');
        $sheet->setCellValue('G1', 'Старая цена');
        $sheet->setCellValue('H1', 'Категория (ID)');
        $sheet->setCellValue('I1', 'Активен (1/0)');
        $sheet->setCellValue('J1', 'Новинка (1/0)');
        $sheet->setCellValue('K1', 'Хит (1/0)');
        $sheet->setCellValue('L1', 'Рекомендуем (1/0)');
        $sheet->setCellValue('M1', 'Изображение (URL)');

        // Style header row
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('A1:M1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Add example row
        $sheet->setCellValue('A2', '');
        $sheet->setCellValue('B2', 'Пример товара');
        $sheet->setCellValue('C2', 'primer-tovara');
        $sheet->setCellValue('D2', 'SKU-001');
        $sheet->setCellValue('E2', 'Это пример описания товара');
        $sheet->setCellValue('F2', '1000');
        $sheet->setCellValue('G2', '1500');
        $sheet->setCellValue('H2', '1');
        $sheet->setCellValue('I2', '1');
        $sheet->setCellValue('J2', '1');
        $sheet->setCellValue('K2', '0');
        $sheet->setCellValue('L2', '0');
        $sheet->setCellValue('M2', '');

        // Auto-size columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'product_import_template_' . date('Y-m-d') . '.xlsx';

        // Write to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'product_template_');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Export products
     */
    public function export()
    {
        if (!class_exists('App\Models\TProduct')) {
            return response()->json(['error' => 'Product module not available'], 404);
        }

        $productClass = 'App\Models\TProduct';
        $products = $productClass::with('catalog')->orderBy('id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Название');
        $sheet->setCellValue('C1', 'Slug');
        $sheet->setCellValue('D1', 'SKU');
        $sheet->setCellValue('E1', 'Описание');
        $sheet->setCellValue('F1', 'Цена');
        $sheet->setCellValue('G1', 'Старая цена');
        $sheet->setCellValue('H1', 'Категория (ID)');
        $sheet->setCellValue('I1', 'Активен (1/0)');
        $sheet->setCellValue('J1', 'Новинка (1/0)');
        $sheet->setCellValue('K1', 'Хит (1/0)');
        $sheet->setCellValue('L1', 'Рекомендуем (1/0)');
        $sheet->setCellValue('M1', 'Изображение (URL)');

        // Style header
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('A1:M1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Fill data
        $row = 2;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product->id);
            $sheet->setCellValue('B' . $row, $product->name);
            $sheet->setCellValue('C' . $row, $product->slug);
            $sheet->setCellValue('D' . $row, $product->sku);
            $sheet->setCellValue('E' . $row, $product->description ?? '');
            $sheet->setCellValue('F' . $row, $product->price);
            $sheet->setCellValue('G' . $row, $product->old_price ?? '');
            $sheet->setCellValue('H' . $row, $product->catalog_id ?? '');
            $sheet->setCellValue('I' . $row, $product->is_active ? '1' : '0');
            $sheet->setCellValue('J' . $row, $product->is_new ? '1' : '0');
            $sheet->setCellValue('K' . $row, $product->is_hot ? '1' : '0');
            $sheet->setCellValue('L' . $row, $product->is_recommended ? '1' : '0');
            $sheet->setCellValue('M' . $row, $product->image ?? '');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'products_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Write to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'product_export_');
        $writer->save($tempFile);

        // Log activity
        TAdminAction::log('exported', 'product', null,
            'Экспорт товаров (количество: ' . count($products) . ')');

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Preview import data before actual import
     */
    public function previewImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        if (!class_exists('App\Models\TProduct')) {
            return response()->json(['error' => 'Product module not available'], 404);
        }

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // Remove header row
        $headers = array_shift($data);

        $preview = [];
        $errors = [];
        $productClass = 'App\Models\TProduct';

        foreach ($data as $index => $row) {
            $rowNum = $index + 2; // +2 because of header and 0-indexing

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $item = [
                'row' => $rowNum,
                'id' => $row[0] ?? null,
                'name' => $row[1] ?? '',
                'slug' => $row[2] ?? '',
                'sku' => $row[3] ?? '',
                'description' => $row[4] ?? '',
                'price' => $row[5] ?? 0,
                'old_price' => $row[6] ?? null,
                'catalog_id' => $row[7] ?? null,
                'is_active' => isset($row[8]) && $row[8] == '1',
                'is_new' => isset($row[9]) && $row[9] == '1',
                'is_hot' => isset($row[10]) && $row[10] == '1',
                'is_recommended' => isset($row[11]) && $row[11] == '1',
                'image' => $row[12] ?? '',
                'status' => 'pending'
            ];

            // Validation
            if (empty($item['name'])) {
                $errors[] = "Строка {$rowNum}: Название обязательно";
                $item['status'] = 'error';
                $item['error'] = 'Название обязательно';
            }

            if (empty($item['sku'])) {
                $errors[] = "Строка {$rowNum}: SKU обязателен";
                $item['status'] = 'error';
                $item['error'] = 'SKU обязателен';
            }

            if (empty($item['price']) || !is_numeric($item['price'])) {
                $errors[] = "Строка {$rowNum}: Цена должна быть числом";
                $item['status'] = 'error';
                $item['error'] = 'Цена должна быть числом';
            }

            // Auto-generate slug if empty
            if (empty($item['slug'])) {
                $item['slug'] = $this->generateSlug($item['name'], $productClass);
            }

            // Check if update or create - search by ID, SKU, or name
            $existing = null;
            if (!empty($item['id'])) {
                $existing = $productClass::find($item['id']);
            }

            if (!$existing && !empty($item['sku'])) {
                $existing = $productClass::where('sku', $item['sku'])->first();
            }

            if (!$existing && !empty($item['name'])) {
                $existing = $productClass::where('name', $item['name'])->first();
            }

            if ($existing) {
                $item['action'] = 'update';
                $item['id'] = $existing->id;
            } else {
                $item['action'] = 'create';
            }

            $preview[] = $item;
        }

        return response()->json([
            'preview' => $preview,
            'total' => count($preview),
            'errors' => $errors,
            'valid' => empty($errors)
        ]);
    }

    /**
     * Import products from preview
     */
    public function import(Request $request)
    {
        $request->validate([
            'items' => 'required|array'
        ]);

        if (!class_exists('App\Models\TProduct')) {
            return response()->json(['error' => 'Product module not available'], 404);
        }

        $productClass = 'App\Models\TProduct';
        $items = $request->input('items');
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($items as $item) {
            try {
                // Skip items with errors
                if (isset($item['status']) && $item['status'] === 'error') {
                    continue;
                }

                // Auto-generate slug if empty
                if (empty($item['slug'])) {
                    $item['slug'] = $this->generateSlug($item['name'], $productClass, $item['id'] ?? null);
                }

                $data = [
                    'name' => $item['name'],
                    'slug' => $item['slug'],
                    'sku' => $item['sku'],
                    'description' => $item['description'] ?? null,
                    'price' => $item['price'],
                    'old_price' => $item['old_price'] ?? null,
                    'catalog_id' => !empty($item['catalog_id']) ? $item['catalog_id'] : null,
                    'is_active' => $item['is_active'] ?? true,
                    'is_new' => $item['is_new'] ?? false,
                    'is_hot' => $item['is_hot'] ?? false,
                    'is_recommended' => $item['is_recommended'] ?? false,
                    'image' => $item['image'] ?? null,
                ];

                if ($item['action'] === 'update' && !empty($item['id'])) {
                    $product = $productClass::find($item['id']);
                    if ($product) {
                        $product->update($data);
                        $updated++;
                    }
                } else {
                    $productClass::create($data);
                    $created++;
                }
            } catch (\Exception $e) {
                $errors[] = "Ошибка в строке {$item['row']}: " . $e->getMessage();
            }
        }

        // Log activity
        TAdminAction::log('imported', 'product', null,
            'Импорт товаров (создано: ' . $created . ', обновлено: ' . $updated . ')');

        return response()->json([
            'success' => true,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors
        ]);
    }

    /**
     * Generate unique slug
     */
    private function generateSlug($name, $modelClass, $excludeId = null)
    {
        $slug = \Illuminate\Support\Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = $modelClass::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
