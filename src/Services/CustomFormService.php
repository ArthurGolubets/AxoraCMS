<?php

namespace HolartWeb\AxoraCMS\Services;

use HolartWeb\AxoraCMS\Models\Callback\TCustomForm;
use HolartWeb\AxoraCMS\Models\Callback\TCustomFormField;
use HolartWeb\AxoraCMS\Models\Callback\TCustomFormSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Custom forms ("Своя форма"): validates and stores submissions.
 *
 * Frontend usage:
 *
 *   // From a controller — validates, stores uploads, notifies the admin:
 *   app(CustomFormService::class)->submitFromRequest('feedback', $request);
 *
 *   // Or with already prepared data:
 *   app(CustomFormService::class)->submit('feedback', ['name' => 'Ivan', 'email' => 'ivan@example.com']);
 *
 * A ready-made endpoint is also available: POST route('axora-cms.forms.submit', 'feedback'),
 * and a Blade component: <x-axora-cms::custom-form code="feedback" />.
 */
class CustomFormService
{
    /**
     * File extensions allowed for "file" fields when the field does not restrict them.
     *
     * @var array<int, string>
     */
    public const DEFAULT_FILE_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'rtf', 'zip', 'jpg', 'jpeg', 'png', 'webp'];

    /**
     * Extensions that are never accepted, whatever the field settings say.
     *
     * @var array<int, string>
     */
    protected const FORBIDDEN_EXTENSIONS = ['php', 'phtml', 'phar', 'html', 'htm', 'svg', 'js', 'exe', 'sh', 'bat'];

    /**
     * Honeypot input: bots fill it, people do not see it.
     */
    public const HONEYPOT_FIELD = '_hp_website';

    public function isAvailable(): bool
    {
        return Schema::hasTable('t_custom_forms');
    }

    /**
     * A form with its fields by code (or the given model, reloaded with fields).
     */
    public function findForm(TCustomForm|string $form, bool $activeOnly = true): ?TCustomForm
    {
        if (! $this->isAvailable()) {
            return null;
        }

        if ($form instanceof TCustomForm) {
            return $form->loadMissing('fields');
        }

        return TCustomForm::with('fields')
            ->where('code', $form)
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->first();
    }

    /**
     * Validate and store a submission coming from the site.
     *
     * @param  array<string, mixed>  $input  Field values keyed by field code; image/file
     *                                       fields accept UploadedFile instances.
     * @param  array{user_id?: ?int, ip?: ?string, user_agent?: ?string}  $context
     *
     * @throws ValidationException
     */
    public function submit(TCustomForm|string $form, array $input, array $context = []): TCustomFormSubmission
    {
        $model = $this->findForm($form);

        if (! $model) {
            throw ValidationException::withMessages(['form' => 'Форма не найдена или отключена']);
        }

        $validated = $this->validate($model, $input, fromAdmin: false);

        return TCustomFormSubmission::create([
            'form_id' => $model->id,
            'data' => $this->normalize($model, $validated, fromAdmin: false),
            'user_id' => $context['user_id'] ?? null,
            'ip' => $context['ip'] ?? null,
            'user_agent' => isset($context['user_agent']) ? Str::limit($context['user_agent'], 250, '') : null,
        ]);
    }

    /**
     * Submit straight from an HTTP request: takes the field inputs and uploaded
     * files, the authenticated user, IP and user agent.
     *
     * Returns null when the honeypot was filled (a bot) — nothing is stored.
     *
     * @throws ValidationException
     */
    public function submitFromRequest(TCustomForm|string $form, Request $request): ?TCustomFormSubmission
    {
        if (filled($request->input(self::HONEYPOT_FIELD))) {
            return null;
        }

        return $this->submit($form, array_replace($request->except(['_token', self::HONEYPOT_FIELD]), $request->allFiles()), [
            'user_id' => $request->user()?->getAuthIdentifier(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Validate admin-panel input (uploads are already stored paths).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed> Normalized data ready for the "data" column.
     *
     * @throws ValidationException
     */
    public function prepareAdminData(TCustomForm $form, array $input): array
    {
        $form->loadMissing('fields');

        return $this->normalize($form, $this->validate($form, $input, fromAdmin: true), fromAdmin: true);
    }

    /**
     * Laravel validation rules for the form fields.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(TCustomForm $form, bool $fromAdmin = false): array
    {
        $rules = [];

        foreach ($form->fields as $field) {
            $valueRules = $this->valueRules($field, $fromAdmin);
            $presence = $field->is_required ? 'required' : 'nullable';

            if ($field->type === 'bool') {
                // A required checkbox is a consent: it must be ticked.
                $rules[$field->code] = $field->is_required ? ['accepted'] : ['nullable', 'boolean'];

                continue;
            }

            if ($field->is_multiple) {
                $rules[$field->code] = [$presence, 'array', 'max:50'];
                $rules[$field->code.'.*'] = ['nullable', ...$valueRules];
            } else {
                $rules[$field->code] = [$presence, ...$valueRules];
            }
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function validate(TCustomForm $form, array $input, bool $fromAdmin): array
    {
        $attributes = $form->fields->mapWithKeys(fn (TCustomFormField $field) => [
            $field->code => $field->name,
            $field->code.'.*' => $field->name,
        ])->all();

        return Validator::make($input, $this->rules($form, $fromAdmin), [], $attributes)->validate();
    }

    /**
     * Rules for a single value of the field.
     *
     * @return array<int, mixed>
     */
    protected function valueRules(TCustomFormField $field, bool $fromAdmin): array
    {
        $settings = $field->settings ?? [];

        return match ($field->type) {
            'text' => ['string', 'max:10000'],
            'email' => ['email', 'max:255'],
            'phone' => ['string', 'max:50', 'regex:/^[0-9+()\-\s.]{5,}$/'],
            'number' => ['numeric'],
            'date' => ['date'],
            'enum' => [Rule::in(array_column($settings['options'] ?? [], 'code'))],
            'entity', 'user' => ['integer', 'min:1'],
            'image' => $fromAdmin
                ? $this->storedPathRules()
                : ['file', 'image', 'max:'.(int) ($settings['max_size'] ?? 5120)],
            'file' => $fromAdmin
                ? $this->storedPathRules()
                : ['file', 'extensions:'.implode(',', $this->allowedExtensions($field)), 'max:'.(int) ($settings['max_size'] ?? 10240)],
            default => ['string', 'max:255'],
        };
    }

    /**
     * Paths of files the administrator uploaded through the panel.
     *
     * @return array<int, string>
     */
    protected function storedPathRules(): array
    {
        return ['string', 'max:500', 'not_regex:/\.\./'];
    }

    /**
     * @return array<int, string>
     */
    public function allowedExtensions(TCustomFormField $field): array
    {
        $configured = collect(explode(',', (string) ($field->settings['extensions'] ?? '')))
            ->map(fn ($ext) => strtolower(trim($ext, " .\t")))
            ->filter()
            ->values()
            ->all();

        $extensions = $configured ?: self::DEFAULT_FILE_EXTENSIONS;

        return array_values(array_diff($extensions, self::FORBIDDEN_EXTENSIONS)) ?: self::DEFAULT_FILE_EXTENSIONS;
    }

    /**
     * Keep only known fields and cast every value to its stored shape.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function normalize(TCustomForm $form, array $validated, bool $fromAdmin): array
    {
        $data = [];

        foreach ($form->fields as $field) {
            $value = $validated[$field->code] ?? null;

            if ($field->type === 'bool') {
                $data[$field->code] = filter_var($value, FILTER_VALIDATE_BOOLEAN);

                continue;
            }

            if ($field->is_multiple) {
                $items = array_map(fn ($item) => $this->castValue($form, $field, $item, $fromAdmin), (array) $value);
                $data[$field->code] = array_values(array_filter($items, fn ($item) => $item !== null && $item !== ''));
            } else {
                $data[$field->code] = $this->castValue($form, $field, $value, $fromAdmin);
            }
        }

        return $data;
    }

    protected function castValue(TCustomForm $form, TCustomFormField $field, mixed $value, bool $fromAdmin): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof UploadedFile) {
            return $fromAdmin ? null : $value->store('custom-forms/'.$form->code, 'public');
        }

        return match ($field->type) {
            'number' => $value + 0,
            'entity', 'user' => (int) $value,
            default => is_string($value) ? trim($value) : $value,
        };
    }
}
