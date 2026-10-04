<?php

namespace HolartWeb\AxoraCMS\Services\ImportExport\Exporters;

use HolartWeb\AxoraCMS\Models\Callback\TCustomForm;
use HolartWeb\AxoraCMS\Models\Callback\TCustomFormSubmission;
use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Submissions of a custom form ("Своя форма").
 *
 * Options: form_id (required), date_from, date_to.
 */
class CustomFormExport extends AbstractExportSource
{
    protected ?TCustomForm $form = null;

    public function key(): string
    {
        return 'custom_form';
    }

    public function label(): string
    {
        return 'Свои формы';
    }

    public function available(): bool
    {
        return TModule::isInstalled('callback') && Schema::hasTable('t_custom_forms');
    }

    public function title(array $options): string
    {
        return 'Экспорт формы «'.$this->form($options)->name.'»';
    }

    public function headers(array $options): array
    {
        return array_merge(['№', 'Дата', 'Источник'], $this->form($options)->fields->pluck('name')->all());
    }

    public function total(array $options): int
    {
        return $this->query($options)->count();
    }

    public function rows(array $options, int $offset, int $limit): array
    {
        $fields = $this->form($options)->fields;

        return $this->query($options)->orderBy('id')->skip($offset)->take($limit)->get()
            ->map(function (TCustomFormSubmission $submission) use ($fields) {
                $row = [$submission->id, $this->date($submission->created_at), $submission->administrator_id ? 'Администратор' : 'Сайт'];
                foreach ($fields as $field) {
                    $value = $submission->data[$field->code] ?? null;
                    $row[] = in_array($field->type, ['image', 'file'], true)
                        ? implode('; ', array_map(fn ($path) => $this->fileUrl($path), array_filter((array) $value)))
                        : $this->cell($value);
                }

                return $row;
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function query(array $options)
    {
        return $this->applyDateRange(TCustomFormSubmission::where('form_id', $this->form($options)->id), $options);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function form(array $options): TCustomForm
    {
        $id = (int) ($options['form_id'] ?? 0);
        if ($this->form?->id !== $id) {
            $this->form = TCustomForm::with('fields')->find($id)
                ?? throw new InvalidArgumentException('Форма не найдена');
        }

        return $this->form;
    }
}
