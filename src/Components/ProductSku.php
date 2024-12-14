<?php

namespace Wsmallnews\Product\Components;

use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Wsmallnews\Product\Models\Product;


class ProductSku extends Component
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
