<?php

namespace HolartWeb\AxoraCMS\Jobs\ImportExport;

use HolartWeb\AxoraCMS\Models\ImportExport\TImportExportTask;
use HolartWeb\AxoraCMS\Services\ImportExport\ExportRegistry;
use HolartWeb\AxoraCMS\Services\ImportExport\ImportExportTaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Exports one chunk of units into the task CSV and queues the next chunk;
 * the last chunk builds the final file.
 */
class ExportChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const CHUNK = 500;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public int $taskId, public int $offset) {}

    public function handle(ImportExportTaskService $service, ExportRegistry $registry): void
    {
        $task = TImportExportTask::find($this->taskId);
        if (! $task || ! in_array($task->status, TImportExportTask::ACTIVE_STATUSES, true)) {
            return;
        }

        $service->markRunning($task);
        $task->refresh();

        $source = $registry->get($task->entity);
        $options = $task->options ?? [];
        $headers = $source->headers($options);

        $rows = $task->total > 0 ? $source->rows($options, $this->offset, self::CHUNK) : [];
        if ($rows) {
            $service->appendCsv($task, $headers, $rows);
        }

        if ($task->fresh()->status !== TImportExportTask::STATUS_RUNNING) {
            return;
        }

        $task->addStats(['rows' => count($rows)]);
        $task->processed = min($task->total, $this->offset + self::CHUNK);
        $task->save();

        if ($task->processed < $task->total) {
            self::dispatch($task->id, $this->offset + self::CHUNK);

            return;
        }

        $task->update(['result_path' => $service->finalizeExportFile($task, $headers)]);
        $service->complete($task);
    }

    public function failed(\Throwable $e): void
    {
        if ($task = TImportExportTask::find($this->taskId)) {
            app(ImportExportTaskService::class)->fail($task, $e->getMessage());
        }
    }
}
