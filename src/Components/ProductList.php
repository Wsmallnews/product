<?php

namespace Wsmallnews\Product\Components;

use Closure;
use Illuminate\Support\Collection;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Support\Components\BaseComponent;
use Wsmallnews\Support\Traits\Components\CanPagination;

class ProductList extends BaseComponent
{
    use CanPagination;

    public Collection $products;

    public string $cardView = 'sn-product::product-cards.simple';

    public string $detailRouteName;

    public function mount()
    {
        $this->products = $this->products ?? collect([]);
    }


    public function render()
    {
        $query = Product::query()->scopeable(...$this->getScopeInfo())->withMediaAndVariants(['main']);

        // 分页
        $this->products = $this->withPagination($query);

        return view('sn-product::livewire.products.index', [
            'paginatorLink' => $this->links
        ])->title('产品列表');
    }
}
