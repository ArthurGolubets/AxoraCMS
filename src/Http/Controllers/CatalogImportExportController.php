<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use HolartWeb\AxoraCMS\Jobs\ImportCatalogsJob;
use HolartWeb\AxoraCMS\Models\TAdminAction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CatalogImportExportController extends Controller
{
    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Название');
        $sheet->setCellValue('C1', 'Slug');
        $sheet->setCellValue('D1', 'Описание');
        $sheet->setCellValue('E1', 'Родительская категория (ID)');
        $sheet->setCellValue('F1', 'Активна (1/0)');
        $sheet->setCellValue('G1', 'Изображение (URL)');

        // Style header row
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Add example row
        $sheet->setCellValue('A2', '');
        $sheet->setCellValue('B2', 'Пример категории');
        $sheet->setCellValue('C2', 'primer-kategorii');
        $sheet->setCellValue('D2', 'Это пример описания категории');
        $sheet->setCellValue('E2', '');
        $sheet->setCellValue('F2', '1');
        $sheet->setCellValue('G2', '');

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'catalog_import_template_'.date('Y-m-d').'.xlsx';

        // Write to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'catalog_template_');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Export catalogs
     */
    public function export()
    {
        if (! class_exists('HolartWeb\AxoraCMS\Models\Shop\TCatalog')) {
            return response()->json(['error' => 'Catalog module not available'], 404);
        }

        $catalogClass = 'HolartWeb\AxoraCMS\Models\Shop\TCatalog';
        $catalogs = $catalogClass::with('parent')->orderBy('id')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Название');
        $sheet->setCellValue('C1', 'Slug');
        $sheet->setCellValue('D1', 'Описание');
        $sheet->setCellValue('E1', 'Родительская категория (ID)');
        $sheet->setCellValue('F1', 'Активна (1/0)');
        $sheet->setCellValue('G1', 'Изображение (URL)');

        // Style header
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Fill data
        $row = 2;
        foreach ($catalogs as $catalog) {
            $sheet->setCellValue('A'.$row, $catalog->id);
            $sheet->setCellValue('B'.$row, $catalog->name);
            $sheet->setCellValue('C'.$row, $catalog->slug);
            $sheet->setCellValue('D'.$row, $catalog->description ?? '');
            $sheet->setCellValue('E'.$row, $catalog->parent_id ?? '');
            $sheet->setCellValue('F'.$row, $catalog->is_active ? '1' : '0');
            $sheet->setCellValue('G'.$row, $catalog->image ?? '');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'catalogs_export_'.date('Y-m-d_H-i-s').'.xlsx';

        // Write to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'catalog_export_');
        $writer->save($tempFile);

        // Log activity
        TAdminAction::log('exported', 'catalog', null,
            'Экспорт категорий (количество: '.count($catalogs).')');

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Preview import data before actual import
     */
    public function previewImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        if (! class_exists('HolartWeb\AxoraCMS\Models\Shop\TCatalog')) {
            return response()->json(['error' => 'Catalog module not available'], 404);
        }

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // Remove header row
        $headers = array_shift($data);

        $preview = [];
        $errors = [];
        $catalogClass = 'HolartWeb\AxoraCMS\Models\Shop\TCatalog';

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
                'description' => $row[3] ?? '',
                'parent_id' => $row[4] ?? null,
                'is_active' => isset($row[5]) && $row[5] == '1',
                'image' => $row[6] ?? '',
                'status' => 'pending',
            ];

            // Validation
            if (empty($item['name'])) {
                $errors[] = "Строка {$rowNum}: Название обязательно";
                $item['status'] = 'error';
                $item['error'] = 'Название обязательно';
            }

            // Auto-generate slug if empty
            if (empty($item['slug'])) {
                $item['slug'] = $this->generateSlug($item['name'], $catalogClass);
            }

            // Check if update or create - search by ID or by name
            $existing = null;
            if (! empty($item['id'])) {
                $existing = $catalogClass::find($item['id']);
            }

            if (! $existing && ! empty($item['name'])) {
                $existing = $catalogClass::where('name', $item['name'])->first();
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
            'valid' => empty($errors),
        ]);
    }

    /**
     * Import catalogs from preview (async with job)
     */
    public function import(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
        ]);

        if (! class_exists('HolartWeb\AxoraCMS\Models\Shop\TCatalog')) {
            return response()->json(['error' => 'Catalog module not available'], 404);
        }

        $items = $request->input('items');
        $importId = Str::uuid()->toString();

        // Initialize progress
        Cache::put("import_progress_{$importId}", [
            'status' => 'queued',
            'created' => 0,
            'updated' => 0,
            'total' => count($items),
            'processed' => 0,
            'errors' => [],
            'percentage' => 0,
        ], 3600);

        // Dispatch job
        ImportCatalogsJob::dispatch($items, $importId);

        return response()->json([
            'success' => true,
            'import_id' => $importId,
            'message' => 'Импорт запущен в фоновом режиме',
        ]);
    }

    /**
     * Check import progress
     */
    public function checkProgress($importId)
    {
        $progress = Cache::get("import_progress_{$importId}");

        if (! $progress) {
            return response()->json(['error' => 'Import not found'], 404);
        }

        return response()->json($progress);
    }

    /**
     * Generate unique slug
     */
    private function generateSlug($name, $modelClass, $excludeId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = $modelClass::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (! $query->exists()) {
                break;
            }

            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
