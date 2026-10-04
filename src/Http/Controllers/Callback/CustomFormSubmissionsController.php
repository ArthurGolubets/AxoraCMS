<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Callback;

use HolartWeb\AxoraCMS\Models\Callback\TCustomForm;
use HolartWeb\AxoraCMS\Models\Callback\TCustomFormSubmission;
use HolartWeb\AxoraCMS\Models\TAdminAction;
use HolartWeb\AxoraCMS\Services\CustomFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Submissions ("записи") of a custom form, as seen in the admin panel.
 */
class CustomFormSubmissionsController extends Controller
{
    public function __construct(protected CustomFormService $forms) {}

    public function index(Request $request, int $formId): JsonResponse
    {
        $form = TCustomForm::findOrFail($formId);

        $submissions = $form->submissions()
            ->when($request->filled('search'), fn ($query) => $query->where('data', 'like', '%'.$request->get('search').'%'))
            ->when($request->get('status') === 'new', fn ($query) => $query->whereNull('viewed_at'))
            ->orderByDesc('id')
            ->paginate(min((int) $request->get('per_page', 20), 100));

        return response()->json($submissions);
    }

    /**
     * Show a submission; opening it marks it as viewed.
     */
    public function show(int $formId, int $id): JsonResponse
    {
        $submission = TCustomFormSubmission::where('form_id', $formId)->findOrFail($id);

        if (! $submission->viewed_at) {
            $submission->update(['viewed_at' => now()]);
        }

        return response()->json($submission);
    }

    public function store(Request $request, int $formId): JsonResponse
    {
        $form = TCustomForm::with('fields')->findOrFail($formId);
        abort_unless($form->admin_can_create, 403, 'Создание записей администратором отключено для этой формы');

        $submission = TCustomFormSubmission::create([
            'form_id' => $form->id,
            'data' => $this->forms->prepareAdminData($form, $request->input('data', [])),
            'administrator_id' => Auth::guard('admin')->id(),
            'viewed_at' => now(),
        ]);

        TAdminAction::log('created', 'custom_form_submission', $submission->id, 'Создана запись формы "'.$form->name.'"');

        return response()->json($submission, 201);
    }

    public function update(Request $request, int $formId, int $id): JsonResponse
    {
        $form = TCustomForm::with('fields')->findOrFail($formId);
        abort_unless($form->admin_can_create, 403, 'Редактирование записей администратором отключено для этой формы');

        $submission = TCustomFormSubmission::where('form_id', $formId)->findOrFail($id);
        $submission->update(['data' => $this->forms->prepareAdminData($form, $request->input('data', []))]);

        TAdminAction::log('updated', 'custom_form_submission', $submission->id, 'Изменена запись формы "'.$form->name.'"');

        return response()->json($submission);
    }

    public function destroy(int $formId, int $id): JsonResponse
    {
        TCustomFormSubmission::where('form_id', $formId)->findOrFail($id)->delete();

        TAdminAction::log('deleted', 'custom_form_submission', $id, 'Удалена запись формы');

        return response()->json(['message' => 'Запись удалена']);
    }

    public function bulkDestroy(Request $request, int $formId): JsonResponse
    {
        $validated = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $deleted = TCustomFormSubmission::where('form_id', $formId)->whereIn('id', $validated['ids'])->delete();

        TAdminAction::log('deleted', 'custom_form_submission', null, 'Удалено записей формы: '.$deleted);

        return response()->json(['message' => 'Записи удалены', 'deleted' => $deleted]);
    }

    public function markAllViewed(int $formId): JsonResponse
    {
        TCustomFormSubmission::where('form_id', $formId)->whereNull('viewed_at')->update(['viewed_at' => now()]);

        return response()->json(['message' => 'Все записи отмечены как просмотренные']);
    }
}
