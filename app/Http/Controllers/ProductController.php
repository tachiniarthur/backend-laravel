<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'image' => 'required|image|max:10240',
            'active' => 'sometimes|boolean',
        ]);

        try {
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('products', 'public');
                $data['image_url'] = url('storage/' . $path);
            }
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
            'image' => 'sometimes|image|max:10240',
            'active' => 'sometimes|boolean',
        ]);

        try {
            if ($request->hasFile('image')) {
                // Remove a imagem antiga se existir
                if ($product->image_url) {
                    $oldPath = str_replace(url('storage/'), '', $product->image_url);
                    Storage::disk('public')->delete($oldPath);
                }

                $path = $request->file('image')->store('products', 'public');
                $data['image_url'] = url('storage/' . $path);
            }
            unset($data['image']);

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
            // Remove a imagem do produto ao deletar
            if ($product->image_url) {
                $oldPath = str_replace(url('storage/'), '', $product->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            $this->productService->delete($product, $request->user());

            return response()->json(['message' => 'Produto removido.']);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
