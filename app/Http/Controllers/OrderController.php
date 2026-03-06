<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function store(Request $request): JsonResponse
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
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json($order, 201);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->listForUser($request->user());

        return response()->json($orders);
    }

    public function indexAll(Request $request): JsonResponse
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $orders = $this->orderService->listAll();

        return response()->json($orders);
    }

    public function updateStatus(Request $request, int $orderId): JsonResponse
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $data = $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        $order = $this->orderService->updateStatus($orderId, $data['status']);

        return response()->json($order);
    }
}
