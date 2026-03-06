<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function createFromCart(User $user, array $items): Order
    {
        return DB::transaction(function () use ($user, $items) {
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            $total = 0;

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if (!$product->active) {
                    throw ValidationException::withMessages([
                        'items' => "O produto \"{$product->name}\" nao esta mais disponivel.",
                    ]);
                }

                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Estoque insuficiente para o produto \"{$product->name}\". Disponivel: {$product->stock}.",
                    ]);
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);

                $total += $product->price * $item['quantity'];
                $product->decreaseStock($item['quantity']);
            }

            // Limpar o carrinho do usuario apos pedido bem-sucedido
            $this->cartService->clear($user);

            return $order->load('items.product');
        });
    }

    public function listForUser(User $user)
    {
        return $user->orders()->with('items.product')->latest()->get();
    }
}
