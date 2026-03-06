<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->cartService->getItems($request->user());

        return response()->json(['items' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $item = $this->cartService->addItem(
                $request->user(),
                $request->product_id,
                $request->quantity
            );

            return response()->json([
                'message' => 'Produto adicionado ao carrinho.',
                'item' => $item,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function update(Request $request, int $cartItemId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $item = $this->cartService->updateItem(
                $request->user(),
                $cartItemId,
                $request->quantity
            );

            return response()->json([
                'message' => 'Quantidade atualizada.',
                'item' => $item,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(Request $request, int $cartItemId): JsonResponse
    {
        $this->cartService->removeItem($request->user(), $cartItemId);

        return response()->json(['message' => 'Item removido do carrinho.']);
    }
}
