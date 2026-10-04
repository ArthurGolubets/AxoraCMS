<?php

namespace HolartWeb\AxoraCMS\Services\ImportExport\Exporters;

use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlock;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlockElement;
use HolartWeb\AxoraCMS\Models\TModule;
use InvalidArgumentException;

/**
 * Elements of one info block with all its fields.
 *
 * Options: infoblock_id (required), date_from, date_to.
 */
class InfoBlockExport extends AbstractExportSource
{
    protected ?TInfoBlock $infoBlock = null;

    public function key(): string
    {
        return 'infoblock';
    }

    public function label(): string
    {
        return 'Инфоблоки';
    }

    public function available(): bool
    {
        return TModule::isInstalled('infoblocks');
    }

    public function title(array $options): string
    {
        return 'Экспорт инфоблока «'.$this->infoBlock($options)->name.'»';
    }

    public function headers(array $options): array
    {
        $headers = ['ID', 'Название', 'Код', 'Раздел', 'Активен', 'Сортировка', 'Создан'];
        foreach ($this->infoBlock($options)->fields as $field) {
            $headers[] = $field->name;
        }

        return $headers;
    }

    public function total(array $options): int
    {
        return $this->query($options)->count();
    }

    public function rows(array $options, int $offset, int $limit): array
    {
        $fields = $this->infoBlock($options)->fields;

        return $this->query($options)
            ->with('section:id,name')
            ->orderBy('sort')
            ->orderBy('id')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function (TInfoBlockElement $element) use ($fields) {
                $properties = is_array($element->properties) ? $element->properties : [];
                $row = [
                    $element->id,
                    $element->name,
                    $element->code,
                    $element->section?->name,
                    $this->yesNo($element->is_active),
                    $element->sort,
                    $this->date($element->created_at),
                ];
                foreach ($fields as $field) {
                    $value = $properties[$field->code] ?? null;
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
        return $this->applyDateRange(TInfoBlockElement::where('info_block_id', $this->infoBlock($options)->id), $options);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function infoBlock(array $options): TInfoBlock
    {
        $id = (int) ($options['infoblock_id'] ?? 0);
        if ($this->infoBlock?->id !== $id) {
            $this->infoBlock = TInfoBlock::with('fields')->find($id)
                ?? throw new InvalidArgumentException('Инфоблок не найден');
        }

        return $this->infoBlock;
    }
}
