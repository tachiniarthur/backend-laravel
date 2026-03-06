<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(): JsonResponse
    {
        $products = $this->productService->list()->map(function ($product) {
            return array_merge($product->toArray(), [
                'available_stock' => $product->available_stock,
            ]);
        });

        return response()->json(['products' => $products]);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->find($id);

        if (!$product) {
            return response()->json(['message' => 'Produto nao encontrado.'], 404);
        }

        return response()->json([
            'product' => array_merge($product->toArray(), [
                'available_stock' => $product->available_stock,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'description' => 'required|string|min:3',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'required|string',
            'active' => 'sometimes|boolean',
        ]);

        try {
            $data['image_url'] = $data['image'];
            unset($data['image']);

            $product = $this->productService->create($data, $request->user());

            return response()->json($product, 201);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = $this->productService->find($id);

        if (!$product) {
            return response()->json(['message' => 'Produto nao encontrado.'], 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|min:3|max:255',
            'description' => 'sometimes|string|min:3',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'image' => 'sometimes|string',
            'active' => 'sometimes|boolean',
        ]);

        try {
            if (isset($data['image'])) {
                $data['image_url'] = $data['image'];
                unset($data['image']);
            }

            $product = $this->productService->update($product, $data, $request->user());

            return response()->json($product);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $product = $this->productService->find($id);

        if (!$product) {
            return response()->json(['message' => 'Produto nao encontrado.'], 404);
        }

        try {
            $this->productService->delete($product, $request->user());

            return response()->json(['message' => 'Produto removido.']);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
