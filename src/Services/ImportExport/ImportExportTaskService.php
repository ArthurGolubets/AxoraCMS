<?php

namespace HolartWeb\AxoraCMS\Services\ImportExport;

use HolartWeb\AxoraCMS\Jobs\ImportExport\ExportChunkJob;
use HolartWeb\AxoraCMS\Jobs\ImportExport\PrepareImportJob;
use HolartWeb\AxoraCMS\Models\ImportExport\TImportExportTask;
use HolartWeb\AxoraCMS\Models\TAdminAction;
use HolartWeb\AxoraCMS\Models\TAdminNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;

/**
 * Lifecycle of import / export tasks: creation, start, completion, failure
 * and the admin notification at the end.
 */
class ImportExportTaskService
{
    public const DISK = 'local';

    public const CSV_DELIMITER = ';';

    public function __construct(
        protected ImportFileReader $reader,
        protected ExportRegistry $exports,
    ) {}

    public function disk()
    {
        return Storage::disk(self::DISK);
    }

    public function directory(TImportExportTask $task): string
    {
        return 'import-export/'.$task->id;
    }

    // --- Import ------------------------------------------------------------------

    /**
     * Store an uploaded file and read its columns for the mapping step.
     */
    public function createImportDraft(UploadedFile $file, ?int $administratorId): TImportExportTask
    {
        $format = strtolower($file->getClientOriginalExtension());

        $task = TImportExportTask::create([
            'type' => TImportExportTask::TYPE_IMPORT,
            'entity' => 'products',
            'status' => TImportExportTask::STATUS_DRAFT,
            'title' => 'Импорт каталога: '.$file->getClientOriginalName(),
            'administrator_id' => $administratorId,
            'format' => $format,
            'original_name' => $file->getClientOriginalName(),
        ]);

        $path = $file->storeAs($this->directory($task), 'source.'.$format, self::DISK);

        try {
            $preview = $this->reader->inspect($this->disk()->path($path), $format);
        } catch (\Throwable $e) {
            $this->disk()->deleteDirectory($this->directory($task));
            $task->delete();

            throw new \InvalidArgumentException('Не удалось прочитать файл: '.$e->getMessage());
        }

        $task->update([
            'source_path' => $path,
            'total' => $preview['total'],
            'options' => ['preview' => $preview],
        ]);

        return $task;
    }

    /**
     * Save the mapping and queue the import.
     *
     * @param  array<string, string>  $mapping  Column key => target.
     * @param  array<string, mixed>  $settings
     */
    public function startImport(TImportExportTask $task, array $mapping, array $settings): TImportExportTask
    {
        $task->update([
            'status' => TImportExportTask::STATUS_PENDING,
            'options' => array_merge($task->options ?? [], [
                'mapping' => array_filter($mapping),
                'settings' => $settings,
            ]),
            'processed' => 0,
            'stats' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
            'errors' => [],
        ]);

        PrepareImportJob::dispatch($task->id);

        return $task->fresh();
    }

    // --- Export ------------------------------------------------------------------

    /**
     * Create and queue an export.
     *
     * @param  array<string, mixed>  $options
     */
    public function createExport(string $entity, array $options, string $format, ?int $administratorId): TImportExportTask
    {
        $source = $this->exports->get($entity);

        $task = TImportExportTask::create([
            'type' => TImportExportTask::TYPE_EXPORT,
            'entity' => $entity,
            'status' => TImportExportTask::STATUS_PENDING,
            'title' => $source->title($options),
            'administrator_id' => $administratorId,
            'format' => $format,
            'options' => $options,
            'total' => $source->total($options),
            'stats' => ['rows' => 0],
        ]);

        ExportChunkJob::dispatch($task->id, 0);

        return $task;
    }

    /**
     * Path of the CSV the export is written into.
     */
    public function exportCsvPath(TImportExportTask $task): string
    {
        return $this->directory($task).'/export.csv';
    }

    /**
     * Append rows to the export CSV (creating it with a BOM and the header).
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function appendCsv(TImportExportTask $task, array $headers, array $rows): void
    {
        $relative = $this->exportCsvPath($task);
        $isNew = ! $this->disk()->exists($relative);
        $this->disk()->makeDirectory($this->directory($task));

        $handle = fopen($this->disk()->path($relative), 'a');
        if ($isNew) {
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers, self::CSV_DELIMITER, '"', '\\');
        }
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($value) => $value === null ? '' : $value, $row), self::CSV_DELIMITER, '"', '\\');
        }
        fclose($handle);
    }

    /**
     * Turn the written CSV into the requested format and return its path.
     */
    public function finalizeExportFile(TImportExportTask $task, array $headers): string
    {
        $csv = $this->exportCsvPath($task);
        if (! $this->disk()->exists($csv)) {
            $this->appendCsv($task, $headers, []);
        }

        $baseName = $this->directory($task).'/'.$task->entity.'-'.now()->format('Y-m-d-His');

        if ($task->format !== 'xlsx') {
            $this->disk()->move($csv, $baseName.'.csv');

            return $baseName.'.csv';
        }

        $reader = new CsvReader;
        $reader->setDelimiter(self::CSV_DELIMITER);
        $reader->setInputEncoding('UTF-8');
        $spreadsheet = $reader->load($this->disk()->path($csv));

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($this->disk()->path($baseName.'.xlsx'));
        $this->disk()->delete($csv);

        return $baseName.'.xlsx';
    }

    // --- Lifecycle ---------------------------------------------------------------

    public function markRunning(TImportExportTask $task): void
    {
        if ($task->status === TImportExportTask::STATUS_PENDING) {
            $task->update(['status' => TImportExportTask::STATUS_RUNNING, 'started_at' => now()]);
        }
    }

    public function complete(TImportExportTask $task): void
    {
        $task->update([
            'status' => TImportExportTask::STATUS_COMPLETED,
            'processed' => $task->total,
            'finished_at' => now(),
        ]);

        $stats = $task->stats ?? [];
        if ($task->type === TImportExportTask::TYPE_IMPORT) {
            $message = sprintf('Создано: %d, обновлено: %d, пропущено: %d, ошибок: %d',
                $stats['created'] ?? 0, $stats['updated'] ?? 0, $stats['skipped'] ?? 0, $stats['failed'] ?? 0);
        } else {
            $message = 'Файл готов к скачиванию · строк: '.($stats['rows'] ?? 0);
        }

        TAdminAction::log($task->type === TImportExportTask::TYPE_IMPORT ? 'imported' : 'exported', 'import_export', $task->id,
            $task->title.' — завершено. '.$message);

        $this->notify($task, $task->title.' — готово', $message);
    }

    public function fail(TImportExportTask $task, string $message): void
    {
        if (in_array($task->status, [TImportExportTask::STATUS_CANCELLED, TImportExportTask::STATUS_COMPLETED], true)) {
            return;
        }

        $task->update([
            'status' => TImportExportTask::STATUS_FAILED,
            'message' => TImportExportTask::cleanMessage($message, 2000),
            'finished_at' => now(),
        ]);

        $this->notify($task, $task->title.' — ошибка', TImportExportTask::cleanMessage($message, 200));
    }

    public function cancel(TImportExportTask $task): void
    {
        $task->update(['status' => TImportExportTask::STATUS_CANCELLED, 'finished_at' => now()]);
    }

    public function delete(TImportExportTask $task): void
    {
        $this->disk()->deleteDirectory($this->directory($task));
        $task->delete();
    }

    protected function notify(TImportExportTask $task, string $title, string $message): void
    {
        if (! Schema::hasTable('t_admin_notifications')) {
            return;
        }

        $page = $task->type === TImportExportTask::TYPE_IMPORT || $task->entity === 'products' ? 'catalog' : 'entities';

        TAdminNotification::record(
            type: 'import_export.'.$task->status,
            title: $title,
            message: $message,
            data: ['task_id' => $task->id],
            link: '/import-export/'.$page.'?task='.$task->id,
        );
    }
}
