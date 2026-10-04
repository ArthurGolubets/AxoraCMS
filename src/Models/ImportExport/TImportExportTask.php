<?php

namespace HolartWeb\AxoraCMS\Models\ImportExport;

use Illuminate\Database\Eloquent\Model;

/**
 * A background import or export task with its progress.
 */
class TImportExportTask extends Model
{
    protected $table = 't_import_export_tasks';

    public const TYPE_IMPORT = 'import';

    public const TYPE_EXPORT = 'export';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Statuses of a task that is still being processed.
     *
     * @var array<int, string>
     */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_RUNNING];

    /**
     * How many error messages are kept on the task.
     */
    public const MAX_ERRORS = 100;

    protected $fillable = [
        'type',
        'entity',
        'status',
        'title',
        'administrator_id',
        'format',
        'source_path',
        'original_name',
        'result_path',
        'options',
        'total',
        'processed',
        'stats',
        'errors',
        'message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'options' => 'array',
        'stats' => 'array',
        'errors' => 'array',
        'total' => 'integer',
        'processed' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected $appends = ['progress', 'is_active', 'download_available'];

    public function getProgressAttribute(): int
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return 100;
        }

        return $this->total > 0 ? (int) min(99, floor($this->processed / $this->total * 100)) : 0;
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function getDownloadAvailableAttribute(): bool
    {
        return $this->type === self::TYPE_EXPORT && $this->status === self::STATUS_COMPLETED && (bool) $this->result_path;
    }

    public function isCancelled(): bool
    {
        return $this->fresh()?->status === self::STATUS_CANCELLED;
    }

    /**
     * Add to the stats counters, e.g. ['created' => 3, 'updated' => 1].
     *
     * @param  array<string, int>  $delta
     */
    public function addStats(array $delta): void
    {
        $stats = $this->stats ?? [];
        foreach ($delta as $key => $count) {
            $stats[$key] = ($stats[$key] ?? 0) + $count;
        }
        $this->stats = $stats;
    }

    /**
     * Keep error messages (up to MAX_ERRORS) and count all of them in stats.
     *
     * @param  array<int, string>  $messages
     */
    public function addErrors(array $messages): void
    {
        if (! $messages) {
            return;
        }

        $messages = array_map([self::class, 'cleanMessage'], $messages);
        $this->errors = array_slice(array_merge($this->errors ?? [], $messages), 0, self::MAX_ERRORS);
        $this->addStats(['failed' => count($messages)]);
    }

    /**
     * A message safe to store and send as JSON: invalid UTF-8 bytes (e.g. from
     * third-party exceptions) are replaced and the length is limited.
     */
    public static function cleanMessage(string $message, int $limit = 500): string
    {
        $message = mb_scrub($message, 'UTF-8');

        return mb_strlen($message) > $limit ? mb_substr($message, 0, $limit).'…' : $message;
    }
}
