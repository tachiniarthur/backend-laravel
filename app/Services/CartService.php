<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Calcula o estoque disponivel para um usuario especifico.
     * Subtrai do estoque total os itens reservados em carrinhos de OUTROS usuarios.
     */
    public function availableStockForUser(Product $product, User $user): int
    {
        $reservedByOthers = CartItem::where('product_id', $product->id)
            ->where('user_id', '!=', $user->id)
            ->sum('quantity');

        return max(0, $product->stock - (int) $reservedByOthers);
    }

    public function getItems(User $user)
    {
        return $user->cartItems()->with('product')->get()->map(function (CartItem $item) use ($user) {
            $availableStock = $this->availableStockForUser($item->product, $user);

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'price' => $item->product->price,
                    'image_url' => $item->product->image_url,
                    'stock' => $item->product->stock,
                    'available_stock' => $availableStock,
                ],
                'available_stock' => max(0, $availableStock - $item->quantity),
            ];
        });
    }

    public function addItem(User $user, int $productId, int $quantity = 1): CartItem
    {
        return DB::transaction(function () use ($user, $productId, $quantity) {
            $product = Product::lockForUpdate()->findOrFail($productId);

            if (!$product->active) {
                throw ValidationException::withMessages([
                    'product_id' => 'Este produto nao esta disponivel.',
                ]);
            }

            $availableForUser = $this->availableStockForUser($product, $user);

            if ($availableForUser <= 0) {
                throw ValidationException::withMessages([
                    'product_id' => 'Produto sem estoque disponivel no momento.',
                ]);
            }

            $item = $user->cartItems()->where('product_id', $productId)->first();
            $currentQty = $item ? $item->quantity : 0;
            $newQuantity = $currentQty + $quantity;

            if ($newQuantity > $availableForUser) {
                $canAdd = max(0, $availableForUser - $currentQty);
                throw ValidationException::withMessages([
                    'quantity' => "Estoque insuficiente. Disponivel: {$canAdd} unidade(s).",
                ]);
            }

            if ($item) {
                $item->quantity = $newQuantity;
                $item->save();
                return $item->load('product');
            }

            return $user->cartItems()->create([
                'product_id' => $productId,
                'quantity' => $quantity,
            ])->load('product');
        });
    }

    public function updateItem(User $user, int $cartItemId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($user, $cartItemId, $quantity) {
            $item = $user->cartItems()->findOrFail($cartItemId);
            $product = Product::lockForUpdate()->findOrFail($item->product_id);

            $availableForUser = $this->availableStockForUser($product, $user);

            if ($quantity > $availableForUser) {
                throw ValidationException::withMessages([
                    'quantity' => "Estoque insuficiente. Maximo disponivel: {$availableForUser} unidade(s).",
                ]);
            }

            $item->update(['quantity' => max(1, $quantity)]);
            return $item->load('product');
        });
    }

    public function removeItem(User $user, int $cartItemId): void
    {
        $user->cartItems()->findOrFail($cartItemId)->delete();
    }

    public function clear(User $user): void
    {
        $user->cartItems()->delete();
    }
}
