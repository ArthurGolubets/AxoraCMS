<?php

namespace HolartWeb\AxoraCMS\Jobs\ImportExport;

use HolartWeb\AxoraCMS\Models\ImportExport\TImportExportTask;
use HolartWeb\AxoraCMS\Services\ImportExport\CatalogImporter;
use HolartWeb\AxoraCMS\Services\ImportExport\ImportExportTaskService;
use HolartWeb\AxoraCMS\Services\ImportExport\ImportFileReader;
use HolartWeb\AxoraCMS\Services\ImportExport\ImportRowException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Imports one chunk of rows, records progress and queues the next chunk.
 */
class ImportChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const CHUNK = 100;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public int $taskId, public int $offset) {}

    public function handle(ImportExportTaskService $service, ImportFileReader $reader, CatalogImporter $importer): void
    {
        $task = TImportExportTask::find($this->taskId);
        if (! $task || $task->status !== TImportExportTask::STATUS_RUNNING) {
            return; // cancelled, failed or deleted
        }

        $options = $task->options ?? [];
        $rows = $reader->readJsonLines($service->disk()->path($options['rows_path']), $this->offset, self::CHUNK);

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $errors = [];

        foreach ($rows as $number => $row) {
            try {
                $result = $importer->importRow($row, $options['mapping'] ?? [], $options['settings'] ?? []);
                $stats[$result]++;
            } catch (ImportRowException $e) {
                $errors[] = "Запись №{$number}: ".$e->getMessage();
            } catch (\Throwable $e) {
                $errors[] = "Запись №{$number}: ошибка сохранения — ".$e->getMessage();
            }
        }

        $task->refresh();
        if ($task->status !== TImportExportTask::STATUS_RUNNING) {
            return;
        }

        $task->addStats($stats);
        $task->addErrors($errors);
        $task->processed = min($task->total, $this->offset + count($rows));
        $task->save();

        if (count($rows) === self::CHUNK && $task->processed < $task->total) {
            self::dispatch($task->id, $this->offset + self::CHUNK);

            return;
        }

        $service->complete($task);
    }

    public function failed(\Throwable $e): void
    {
        if ($task = TImportExportTask::find($this->taskId)) {
            app(ImportExportTaskService::class)->fail($task, $e->getMessage());
        }
    }
}
