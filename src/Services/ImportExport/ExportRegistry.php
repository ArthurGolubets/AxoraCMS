<?php

namespace HolartWeb\AxoraCMS\Services\ImportExport;

use HolartWeb\AxoraCMS\Services\ImportExport\Exporters\CallbackExport;
use HolartWeb\AxoraCMS\Services\ImportExport\Exporters\CustomFormExport;
use HolartWeb\AxoraCMS\Services\ImportExport\Exporters\ExportSource;
use HolartWeb\AxoraCMS\Services\ImportExport\Exporters\InfoBlockExport;
use HolartWeb\AxoraCMS\Services\ImportExport\Exporters\OrdersExport;
use HolartWeb\AxoraCMS\Services\ImportExport\Exporters\ProductsExport;
use InvalidArgumentException;

/**
 * Export sources by entity key. Sources of modules that are not installed
 * are not available.
 */
class ExportRegistry
{
    /**
     * @return array<string, class-string<ExportSource>>
     */
    protected function classes(): array
    {
        return [
            'products' => ProductsExport::class,
            'orders' => OrdersExport::class,
            'infoblock' => InfoBlockExport::class,
            'callback' => CallbackExport::class,
            'custom_form' => CustomFormExport::class,
        ];
    }

    public function get(string $key): ExportSource
    {
        $class = $this->classes()[$key] ?? null;
        $source = $class ? new $class : null;

        if (! $source || ! $source->available()) {
            throw new InvalidArgumentException('Этот тип данных недоступен для экспорта');
        }

        return $source;
    }

    /**
     * @return array<string, bool> Entity key => available.
     */
    public function availability(): array
    {
        return collect($this->classes())->map(fn ($class) => (new $class)->available())->all();
    }
}
