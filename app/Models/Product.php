<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'image_url', 'stock', 'active'];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'active' => 'boolean',
    ];

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function getReservedQuantityAttribute(): int
    {
        return $this->cartItems()->sum('quantity');
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->stock - $this->reserved_quantity);
    }

    public function decreaseStock(int $quantity): void
    {
        $this->decrement('stock', $quantity);
    }
}
