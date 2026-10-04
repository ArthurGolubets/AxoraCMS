<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\ImportExport;

use HolartWeb\AxoraCMS\Models\Callback\TCustomForm;
use HolartWeb\AxoraCMS\Models\ImportExport\TImportExportTask;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlock;
use HolartWeb\AxoraCMS\Services\ImportExport\CatalogImporter;
use HolartWeb\AxoraCMS\Services\ImportExport\Exporters\CallbackExport;
use HolartWeb\AxoraCMS\Services\ImportExport\ExportRegistry;
use HolartWeb\AxoraCMS\Services\ImportExport\ImportExportTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Module "Импорт/Экспорт": catalog import with column mapping, exports of
 * catalog / orders / info blocks / feedback, task progress and downloads.
 */
class ImportExportController extends Controller
{
    public function __construct(
        protected ImportExportTaskService $tasks,
        protected ExportRegistry $exports,
        protected CatalogImporter $importer,
    ) {}

    /**
     * What can be exported on this installation (depends on installed modules).
     */
    public function meta(): JsonResponse
    {
        $available = $this->exports->availability();

        return response()->json([
            'max_upload_bytes' => $this->maxUploadBytes(),
            'exports' => $available,
            'infoblocks' => $available['infoblock'] ? TInfoBlock::orderBy('name')->get(['id', 'name']) : [],
            'custom_forms' => $available['custom_form'] ? TCustomForm::orderBy('name')->get(['id', 'name']) : [],
            'callback_kinds' => $available['callback'] ? CallbackExport::KINDS : [],
        ]);
    }

    /**
     * Recent tasks. scope=catalog — catalog import / export, scope=entities — other exports.
     */
    public function index(Request $request): JsonResponse
    {
        $tasks = TImportExportTask::query()
            ->where('status', '!=', TImportExportTask::STATUS_DRAFT)
            ->when($request->get('scope') === 'catalog', fn ($q) => $q->where('entity', 'products'))
            ->when($request->get('scope') === 'entities', fn ($q) => $q->where('entity', '!=', 'products'))
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(fn (TImportExportTask $task) => $this->present($task));

        return response()->json($tasks);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->present(TImportExportTask::findOrFail($id)));
    }

    /**
     * Upload a file for import: returns its columns, sample rows, mapping targets and a suggested mapping.
     */
    public function upload(Request $request): JsonResponse
    {
        $limitMb = max(1, (int) floor($this->maxUploadBytes() / 1048576));

        $request->validate([
            'file' => 'required|file|max:'.($limitMb * 1024).'|extensions:xlsx,xls,csv,xml',
        ], [
            'file.required' => "Файл не получен. Возможно, он больше лимита сервера ({$limitMb} МБ)",
            'file.uploaded' => "Файл не загрузился: он больше лимита сервера ({$limitMb} МБ). Увеличьте upload_max_filesize и post_max_size в php.ini",
            'file.max' => "Файл больше {$limitMb} МБ",
            'file.extensions' => 'Поддерживаются файлы Excel (xlsx, xls), CSV и XML',
        ]);

        try {
            $task = $this->tasks->createImportDraft($request->file('file'), Auth::guard('admin')->id());
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        $preview = $task->options['preview'];
        $targets = $this->importer->targets();

        return response()->json([
            'task' => $this->present($task),
            'columns' => $preview['columns'],
            'sample' => $preview['sample'],
            'targets' => $targets,
            'suggested_mapping' => $this->suggestMapping($preview['columns'], $targets),
        ]);
    }

    public function startImport(Request $request, int $id): JsonResponse
    {
        $task = TImportExportTask::where('type', TImportExportTask::TYPE_IMPORT)->findOrFail($id);
        abort_unless($task->status === TImportExportTask::STATUS_DRAFT, 422, 'Этот импорт уже запущен');

        $validated = $request->validate([
            'mapping' => 'required|array',
            'mapping.*' => 'nullable|string|max:150',
            'settings' => 'array',
            'settings.match_by' => 'in:sku,name',
            'settings.mode' => 'in:create_update,create_only,update_only',
            'settings.create_categories' => 'boolean',
            'settings.default_catalog_id' => 'nullable|integer|exists:t_catalogs,id',
            'settings.download_images' => 'boolean',
        ]);

        $targets = array_filter($validated['mapping']);
        if (! $targets) {
            throw ValidationException::withMessages(['mapping' => 'Сопоставьте хотя бы одну колонку']);
        }
        if (count($targets) !== count(array_unique($targets))) {
            throw ValidationException::withMessages(['mapping' => 'Одно поле выбрано для нескольких колонок']);
        }
        $matchBy = $validated['settings']['match_by'] ?? 'sku';
        if (! in_array($matchBy, $targets, true) && ($validated['settings']['mode'] ?? 'create_update') !== 'create_only') {
            throw ValidationException::withMessages(['mapping' => 'Сопоставьте колонку «'.($matchBy === 'name' ? 'Название' : 'Артикул').'» — по ней ищутся существующие товары']);
        }

        return response()->json($this->present($this->tasks->startImport($task, $validated['mapping'], $validated['settings'] ?? [])));
    }

    public function startExport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity' => 'required|string|in:products,orders,infoblock,callback,custom_form',
            'format' => 'required|in:xlsx,csv',
            'options' => 'array',
            'options.catalog_ids' => 'array',
            'options.catalog_ids.*' => 'integer',
            'options.date_from' => 'nullable|date_format:Y-m-d',
            'options.date_to' => 'nullable|date_format:Y-m-d',
            'options.delivery_status' => 'nullable|string|max:20',
            'options.payment_status' => 'nullable|string|max:20',
            'options.infoblock_id' => 'nullable|integer',
            'options.form_id' => 'nullable|integer',
            'options.kind' => 'nullable|string|max:30',
        ]);

        try {
            $task = $this->tasks->createExport($validated['entity'], $validated['options'] ?? [], $validated['format'], Auth::guard('admin')->id());
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['entity' => $e->getMessage()]);
        }

        return response()->json($this->present($task->fresh()), 201);
    }

    public function cancel(int $id): JsonResponse
    {
        $task = TImportExportTask::findOrFail($id);
        if ($task->is_active) {
            $this->tasks->cancel($task);
        }

        return response()->json($this->present($task->fresh()));
    }

    public function destroy(int $id): JsonResponse
    {
        $task = TImportExportTask::findOrFail($id);
        abort_if($task->is_active, 422, 'Сначала остановите задачу');

        $this->tasks->delete($task);

        return response()->json(['message' => 'Задача удалена']);
    }

    public function download(int $id): StreamedResponse
    {
        $task = TImportExportTask::findOrFail($id);
        abort_unless($task->download_available && $this->tasks->disk()->exists($task->result_path), 404);

        return $this->tasks->disk()->download($task->result_path, basename($task->result_path));
    }

    /**
     * Largest file PHP accepts: the smaller of upload_max_filesize / post_max_size (capped at 50 MB).
     */
    protected function maxUploadBytes(): int
    {
        $toBytes = function (string $value): int {
            $value = trim($value);
            $number = (float) $value;

            return (int) match (strtolower(substr($value, -1))) {
                'g' => $number * 1024 ** 3,
                'm' => $number * 1024 ** 2,
                'k' => $number * 1024,
                default => $number,
            };
        };

        $limits = array_filter([
            $toBytes((string) ini_get('upload_max_filesize')),
            $toBytes((string) ini_get('post_max_size')),
        ]);

        return min([50 * 1024 * 1024, ...$limits]);
    }

    /**
     * Task data for the UI (without the bulky import preview).
     *
     * @return array<string, mixed>
     */
    protected function present(TImportExportTask $task): array
    {
        $data = $task->toArray();
        unset($data['options']['preview'], $data['options']['rows_path'], $data['source_path'], $data['result_path']);

        return $data;
    }

    /**
     * Match columns to targets by label, key or code (case-insensitive).
     *
     * @param  array<int, array{key: string, label: string, suggest?: string}>  $columns
     * @param  array<int, array{group: string, items: array<int, array<string, string>>}>  $targets
     * @return array<string, string>
     */
    protected function suggestMapping(array $columns, array $targets): array
    {
        $normalize = fn (?string $value) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $value)));
        $index = [];
        foreach ($targets as $group) {
            foreach ($group['items'] as $item) {
                foreach ([$item['label'], $item['key'], $item['hint'] ?? null] as $alias) {
                    $alias = $normalize($alias);
                    if ($alias !== '' && ! isset($index[$alias])) {
                        $index[$alias] = $item['key'];
                    }
                }
            }
        }
        // Common header aliases.
        foreach (['артикул' => 'sku', 'sku' => 'sku', 'наименование' => 'name', 'название товара' => 'name',
            'стоимость' => 'price', 'описание' => 'description', 'изображение' => 'main_image', 'картинка' => 'main_image',
            'picture' => 'main_image', 'раздел' => 'category'] as $alias => $key) {
            $index[$alias] ??= $key;
        }

        $mapping = [];
        $used = [];

        // Resolved XML references hint their target ("categoryId → category (путь)" → Категория).
        foreach ($columns as $column) {
            $target = $column['suggest'] ?? null;
            if ($target && ! in_array($target, $used, true)) {
                $mapping[$column['key']] = $target;
                $used[] = $target;
            }
        }

        foreach ($columns as $column) {
            if (isset($mapping[$column['key']])) {
                continue;
            }
            $target = $index[$normalize($column['label'])] ?? null;
            if ($target && ! in_array($target, $used, true)) {
                $mapping[$column['key']] = $target;
                $used[] = $target;
            }
        }

        return $mapping;
    }
}
