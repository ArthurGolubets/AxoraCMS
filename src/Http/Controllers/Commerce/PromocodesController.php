<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Commerce;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use HolartWeb\HolartCMS\Models\Commerce\TPromocodes;

class PromocodesController extends Controller
{
    public function index(Request $request)
    {
        $query = TPromocodes::query();

        // Search
        if ($request->has('search') && $request->search !== '') {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by type
        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $promocodes = $query->paginate($perPage);

        return response()->json($promocodes);
    }

    public function show($id)
    {
        $promocode = TPromocodes::with('orders')->findOrFail($id);
        return response()->json($promocode);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:t_promocodes,code',
            'value' => 'required|numeric|min:0',
            'type' => 'required|in:fiat,percent',
            'max_usage' => 'required|integer|min:0',
            'date_active_from' => 'nullable|date',
            'date_active_to' => 'nullable|date|after:date_active_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $promocode = TPromocodes::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Промокод создан успешно',
            'data' => $promocode
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $promocode = TPromocodes::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:t_promocodes,code,' . $id,
            'value' => 'required|numeric|min:0',
            'type' => 'required|in:fiat,percent',
            'max_usage' => 'required|integer|min:0',
            'date_active_from' => 'nullable|date',
            'date_active_to' => 'nullable|date|after:date_active_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $promocode->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Промокод обновлен успешно',
            'data' => $promocode
        ]);
    }

    public function destroy($id)
    {
        $promocode = TPromocodes::findOrFail($id);
        $promocode->delete();

        return response()->json([
            'success' => true,
            'message' => 'Промокод удален успешно'
        ]);
    }

    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $promocode = TPromocodes::where('code', $request->code)->first();

        if (!$promocode) {
            return response()->json([
                'success' => false,
                'message' => 'Промокод не найден'
            ], 404);
        }

        if (!$promocode->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Промокод неактивен или исчерпан'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $promocode
        ]);
    }
}
