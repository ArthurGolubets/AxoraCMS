<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Commerce;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use HolartWeb\HolartCMS\Models\Commerce\TOrdersData;

class OrdersDataController extends Controller
{
    public function index()
    {
        $settings = TOrdersData::all()->mapWithKeys(function ($item) {
            return [$item->key => $item->getTypedValue()];
        });

        // Add available payment providers based on installed modules
        $availableProviders = ['transfer'];

        // Check if Yookassa module is installed
        if (class_exists('\App\Services\YookassaService')) {
            $availableProviders[] = 'yookassa';
        }

        // Check if Sberbank module is installed
        if (class_exists('\App\Services\SberbankService')) {
            $availableProviders[] = 'sberbank';
        }

        $settings['available_providers'] = $availableProviders;

        return response()->json($settings);
    }

    public function show($key)
    {
        $value = TOrdersData::getValue($key);

        return response()->json([
            'key' => $key,
            'value' => $value
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'delivery_zones' => 'nullable|array',
            'delivery_zones.*.name' => 'required|string',
            'delivery_zones.*.code' => 'required|string',
            'delivery_zones.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->settings as $key => $value) {
            $type = $this->detectType($value);
            TOrdersData::setValue($key, $value, $type);
        }

        // Save delivery zones if provided
        if ($request->has('delivery_zones')) {
            TOrdersData::setValue('delivery_zones', $request->delivery_zones, TOrdersData::TYPE_JSON);
        }

        return response()->json([
            'success' => true,
            'message' => 'Настройки сохранены успешно'
        ]);
    }

    public function update(Request $request, $key)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $type = $this->detectType($request->value);
        TOrdersData::setValue($key, $request->value, $type);

        return response()->json([
            'success' => true,
            'message' => 'Настройка обновлена успешно'
        ]);
    }

    public function destroy($key)
    {
        TOrdersData::where('key', $key)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Настройка удалена успешно'
        ]);
    }

    private function detectType($value): string
    {
        if (is_bool($value)) {
            return TOrdersData::TYPE_BOOLEAN;
        }

        if (is_int($value)) {
            return TOrdersData::TYPE_INTEGER;
        }

        if (is_array($value)) {
            return TOrdersData::TYPE_JSON;
        }

        if (is_string($value) && strlen($value) > 255) {
            return TOrdersData::TYPE_TEXT;
        }

        return TOrdersData::TYPE_STRING;
    }
}
