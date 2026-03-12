<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Commerce;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use HolartWeb\HolartCMS\Models\Commerce\TPaymentTransaction;

class PaymentTransactionsController extends Controller
{
    public function index(Request $request)
    {
        $query = TPaymentTransaction::with('order');

        // Search
        if ($request->has('search') && $request->search !== '') {
            $query->where('transaction_id', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        return response()->json($transactions);
    }

    public function show($id)
    {
        $transaction = TPaymentTransaction::with('order')->findOrFail($id);
        return response()->json($transaction);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string|unique:t_payment_transactions,transaction_id',
            'order_id' => 'required|integer|exists:t_orders,id',
            'link' => 'nullable|url',
            'status' => 'required|in:pending,success,cancel',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction = TPaymentTransaction::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Транзакция создана успешно',
            'data' => $transaction
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $transaction = TPaymentTransaction::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,success,cancel',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction->update($request->all());

        // Update order payment status if transaction status changed
        if ($request->has('status') && $transaction->order) {
            $transaction->order->update([
                'payment_status' => $request->status
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Транзакция обновлена успешно',
            'data' => $transaction
        ]);
    }

    public function destroy($id)
    {
        $transaction = TPaymentTransaction::findOrFail($id);
        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Транзакция удалена успешно'
        ]);
    }
}
