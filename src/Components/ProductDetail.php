<?php

namespace Wsmallnews\Product\Components;

use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Wsmallnews\Product\Models\Product;


class ProductDetail extends Component
{
    use WithPagination;
    use WithoutUrlPagination;

    public Product $product;

    public array $buyInfo = [
        "product_id" => 30,
        "product_sku_price_id" => 770,
        "product_num" => 1,
        "product_attributes" => [],
        "delivery_type" => "selfetch",
        "use_store_id" => 0
    ];


    public function mount($id)
    {
        $query = Product::show()->with([
            'skus.children',
            'attributes' => function ($builder) {
                $builder->with(['children.attribute_repository', 'attribute_repository']);
            }
        ]);

        $this->product = $query->findOrFail($id);
    }



    // public function buy()
    // {
    //     // 跳转到结算页面
    //     // return $this->redirectRoute('order-confirm');
    //     $redirectData = [
    //         'type' => 'product',
    //         'from' => 'product-detail',
    //         'relate_items' => [$this->buyInfo],
    //     ];

    //     return $this->redirect('/shop/order-confirm?' . http_build_query($redirectData));
    // }



    public function render()
    {
        return view('sn-product::livewire.products.detail', [
            'product' => $this->product,
            'skus' => $this->product->skus,
            'skuPrices' => $this->product->skuPrices
        ])->title('产品详情');
    }
}
