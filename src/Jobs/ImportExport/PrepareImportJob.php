<?php

namespace HolartWeb\AxoraCMS\Jobs\ImportExport;

use HolartWeb\AxoraCMS\Models\ImportExport\TImportExportTask;
use HolartWeb\AxoraCMS\Services\ImportExport\ImportExportTaskService;
use HolartWeb\AxoraCMS\Services\ImportExport\ImportFileReader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * First step of an import: converts the uploaded file into JSON Lines and
 * queues the chunk jobs.
 */
class PrepareImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public int $taskId) {}

    public function handle(ImportExportTaskService $service, ImportFileReader $reader): void
    {
        $task = TImportExportTask::find($this->taskId);
        if (! $task || $task->status === TImportExportTask::STATUS_CANCELLED) {
            return;
        }

        $service->markRunning($task);

        $rowsPath = $service->directory($task).'/rows.jsonl';
        $total = $reader->toJsonLines($service->disk()->path($task->source_path), $task->format, $service->disk()->path($rowsPath));

        $task->update(['total' => $total, 'options' => array_merge($task->options ?? [], ['rows_path' => $rowsPath])]);

        if ($total === 0) {
            $service->complete($task->fresh());

            return;
        }

        ImportChunkJob::dispatch($task->id, 0);
    }

    public function failed(\Throwable $e): void
    {
        if ($task = TImportExportTask::find($this->taskId)) {
            app(ImportExportTaskService::class)->fail($task, $e->getMessage());
        }
    }
}
