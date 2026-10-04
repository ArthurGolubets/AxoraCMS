<?php

namespace HolartWeb\AxoraCMS\Services\ImportExport\Exporters;

/**
 * A data set that can be exported step by step ("единица" — order, element, record...).
 */
interface ExportSource
{
    /**
     * Entity key stored on the task ("orders", "infoblock", ...).
     */
    public function key(): string;

    public function label(): string;

    /**
     * Whether the module providing this data is installed.
     */
    public function available(): bool;

    /**
     * Human readable title of the task for these options.
     *
     * @param  array<string, mixed>  $options
     */
    public function title(array $options): string;

    /**
     * Column headers.
     *
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    public function headers(array $options): array;

    /**
     * Number of units to export.
     *
     * @param  array<string, mixed>  $options
     */
    public function total(array $options): int;

    /**
     * Table rows for units [$offset, $offset + $limit), aligned with headers().
     *
     * @param  array<string, mixed>  $options
     * @return array<int, array<int, scalar|null>>
     */
    public function rows(array $options, int $offset, int $limit): array;
}
