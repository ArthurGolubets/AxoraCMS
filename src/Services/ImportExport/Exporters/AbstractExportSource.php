<?php

namespace HolartWeb\AxoraCMS\Services\ImportExport\Exporters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Shared helpers: date range filter and value formatting.
 */
abstract class AbstractExportSource implements ExportSource
{
    /**
     * Apply "date_from" / "date_to" (Y-m-d) options to created_at.
     *
     * @param  array<string, mixed>  $options
     */
    protected function applyDateRange(Builder $query, array $options, string $column = 'created_at'): Builder
    {
        if (! empty($options['date_from'])) {
            $query->where($column, '>=', $options['date_from'].' 00:00:00');
        }
        if (! empty($options['date_to'])) {
            $query->where($column, '<=', $options['date_to'].' 23:59:59');
        }

        return $query;
    }

    protected function date(mixed $value): string
    {
        return $value ? Carbon::parse($value)->format('d.m.Y H:i') : '';
    }

    protected function yesNo(mixed $value): string
    {
        return $value ? 'Да' : 'Нет';
    }

    /**
     * Any stored value as a cell: arrays are joined, objects become "text (url)" or JSON.
     */
    protected function cell(mixed $value): string|int|float|null
    {
        if ($value === null || is_scalar($value)) {
            return is_bool($value) ? $this->yesNo($value) : $value;
        }

        if (is_array($value)) {
            if (isset($value['text'], $value['url']) && count($value) <= 3) {
                return trim($value['text'].' ('.$value['url'].')');
            }
            if (array_is_list($value) && array_reduce($value, fn ($carry, $item) => $carry && (is_scalar($item) || $item === null), true)) {
                return implode('; ', array_map('strval', $value));
            }
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    protected function fileUrl(?string $path): string
    {
        if (! $path) {
            return '';
        }

        return preg_match('#^(https?:)?//#', $path) ? $path : url('storage/'.ltrim($path, '/'));
    }
}
