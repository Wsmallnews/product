<?php

namespace Wsmallnews\Product\Components;

use Illuminate\Support\Collection;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Models\SkuPrice;
use Wsmallnews\Support\Components\BaseComponent;

class ProductDetail extends BaseComponent
{
    public Product $product;


    public SkuPrice $choosedSkuPrice;


    public array $buyInfo = [
        "product_id" => 0,
        "product_sku_price_id" => 0,
        "product_num" => 1,
        "product_attributes" => [],
        "delivery_type" => "",
    ];


    public function mount($id)
    {
        $query = Product::query()->scopeable(...$this->getScopeInfo())->show()->with([
            'skus.children',
            'attributes' => function ($builder) {
                $builder->with(['children.attribute_repository', 'attribute_repository']);
            }
        ]);

        $this->product = $query->findOrFail($id);
    }



    public function render()
    {
        return view('sn-product::livewire.products.detail', [
            'product' => $this->product,
            'skus' => $this->product->skus,
            'skuPrices' => $this->product->skuPrices
        ])->title('产品详情');
    }
}
