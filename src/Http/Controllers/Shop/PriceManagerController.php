<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Shop;

use HolartWeb\AxoraCMS\Models\TAdminAction;
use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\PriceManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

/**
 * "Менеджер цен": bulk price changes for selected products and categories.
 */
class PriceManagerController extends Controller
{
    public function __construct(protected PriceManagerService $prices)
    {
        // Available only when the "Менеджер цен" module is installed.
        abort_unless(TModule::isInstalled('pricemanager'), 404);
    }

    public function preview(Request $request): JsonResponse
    {
        return response()->json($this->prices->preview($this->validated($request)));
    }

    public function apply(Request $request): JsonResponse
    {
        $options = $this->validated($request);
        $counts = $this->prices->apply($options);

        $sign = $options['direction'] === 'decrease' ? '−' : '+';
        $unit = $options['mode'] === 'percent' ? '%' : ' ₽';
        TAdminAction::log('updated', 'product_prices', null,
            "Массовое изменение цен {$sign}{$options['value']}{$unit}: товаров {$counts['products']}, вариантов {$counts['variants']}",
            ['options' => $options, 'counts' => $counts]);

        return response()->json([
            'message' => 'Цены обновлены',
            ...$counts,
        ]);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function validated(Request $request): array
    {
        $options = $request->validate([
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer',
            'catalog_ids' => 'nullable|array',
            'catalog_ids.*' => 'integer',
            'include_subcategories' => 'boolean',
            'direction' => 'required|in:increase,decrease',
            'mode' => 'required|in:percent,fixed',
            'value' => 'required|numeric|gt:0|max:1000000',
            'apply_to_variants' => 'boolean',
            'rounding' => 'nullable|in:0,1,10,100',
            'old_price' => 'nullable|in:keep,previous,clear',
        ], [
            'value.gt' => 'Укажите, на сколько изменить цену',
        ]);

        if (empty($options['product_ids']) && empty($options['catalog_ids'])) {
            throw ValidationException::withMessages(['product_ids' => 'Выберите товары или категории']);
        }

        if ($options['mode'] === 'percent' && $options['direction'] === 'decrease' && $options['value'] > 100) {
            throw ValidationException::withMessages(['value' => 'Нельзя уменьшить цену больше чем на 100%']);
        }

        return $options;
    }
}
