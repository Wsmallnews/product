<?php

namespace Wsmallnews\Product\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'publisher_type' => (new User)->getMorphClass(),
            'publisher_id' => User::factory(),
            'title' => fake()->sentence(3),
            'subtitle' => fake()->sentence(6),
            'spec_type' => ProductSpecType::Single,
            'price' => 0,
            'stock_type' => 'infinite',
            'status' => ProductStatus::Up,
        ];
    }

    /**
     * 指定规格类型。
     */
    public function specType(ProductSpecType $specType): static
    {
        return $this->state(fn (array $attributes): array => [
            'spec_type' => $specType,
        ]);
    }
}
