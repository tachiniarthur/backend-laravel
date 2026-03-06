<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Service that encapsulates business logic for products.
 */
class ProductService
{
    /**
     * Return only active products.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function list()
    {
        return Product::active()->get();
    }

    /**
     * Find a product by id.
     */
    public function find(int $id): ?Product
    {
        return Product::find($id);
    }

    /**
     * Create a product. If an actor is provided it must be admin.
     *
     * @param array $data
     * @param User|null $actor
     * @return Product
     * @throws AuthorizationException
     */
    public function create(array $data, ?User $actor = null): Product
    {
        if ($actor && !$actor->is_admin) {
            throw new AuthorizationException('Only admins can create products.');
        }

        return Product::create($data);
    }

    /**
     * Update a product. Actor must be admin when provided.
     *
     * @param Product $product
     * @param array $data
     * @param User|null $actor
     * @return Product
     * @throws AuthorizationException
     */
    public function update(Product $product, array $data, ?User $actor = null): Product
    {
        if ($actor && !$actor->is_admin) {
            throw new AuthorizationException('Only admins can update products.');
        }

        $product->update($data);

        return $product;
    }

    /**
     * Delete a product. Actor must be admin when provided.
     *
     * @param Product $product
     * @param User|null $actor
     * @return void
     * @throws AuthorizationException
     */
    public function delete(Product $product, ?User $actor = null): void
    {
        if ($actor && !$actor->is_admin) {
            throw new AuthorizationException('Only admins can delete products.');
        }

        $product->delete();
    }
}
