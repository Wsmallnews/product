<?php

namespace Wsmallnews\Product\Components;

use Illuminate\Support\Collection;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Support\Components\BaseComponent;

class ProductSku extends BaseComponent
{

    public Product $product;

    public function mount()
    {

    }


    public function render()
    {
        return view('sn-product::livewire.products.sku', [
            'skus' => $this->product->skus,
            'skuPrices' => $this->product->skuPrices
        ])->title('产品详情');
    }
}
