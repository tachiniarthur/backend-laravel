<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $order = $this->orderService->createFromCart(
                $request->user(),
                $data['items']
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        return response()->json($order, 201);
    }

    public function index(Request $request)
    {
        $orders = $this->orderService->listForUser($request->user());
        return response()->json($orders);
    }
}
