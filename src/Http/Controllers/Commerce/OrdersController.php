<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Commerce;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\TOrders;
use App\Models\TOrderItems;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $query = TOrders::with(['items', 'promocode', 'paymentTransaction']);

        // Search
        if ($request->has('search') && $request->search !== '') {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by payment status
        if ($request->has('payment_status') && $request->payment_status !== '') {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by delivery type
        if ($request->has('delivery_type') && $request->delivery_type !== '') {
            $query->where('delivery_type', $request->delivery_type);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }

    public function show($id)
    {
        $order = TOrders::with(['items', 'promocode', 'paymentTransaction'])->findOrFail($id);
        return response()->json($order);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'delivery_type' => 'required|in:pickup,courier,post',
            'payment_type' => 'required|in:online,cash',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.product_name' => 'required|string',
            'items.*.amount' => 'required|integer|min:1',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order = TOrders::create($request->except('items'));

        // Create order items
        foreach ($request->items as $item) {
            TOrderItems::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'amount' => $item['amount'],
                'total_price' => $item['total_price'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Заказ создан успешно',
            'data' => $order->load('items')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $order = TOrders::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
            'delivery_status' => 'nullable|in:pending,processing,shipped,delivered,cancelled',
            'delivery_type' => 'nullable|in:pickup,courier,post',
            'payment_type' => 'nullable|in:online,cash,card',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Заказ обновлен успешно',
            'data' => $order->fresh()
        ]);
    }

    public function destroy($id)
    {
        $order = TOrders::findOrFail($id);

        // Delete order items
        $order->items()->delete();

        // Delete order
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Заказ удален успешно'
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = TOrders::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order->update(['payment_status' => $request->payment_status]);

        return response()->json([
            'success' => true,
            'message' => 'Статус заказа изменен',
            'data' => $order->fresh()
        ]);
    }
}
