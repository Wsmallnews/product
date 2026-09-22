<?php

namespace Wsmallnews\Product\Livewire\Components\Product;

use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\WithoutUrlPagination;
use Wsmallnews\Product\Livewire\Components\Base;
use Wsmallnews\Product\Support\Utils;
use Wsmallnews\Support\Livewire\Concerns\CanBeContained;
use Wsmallnews\Support\Livewire\Concerns\CanPagination;

class Products extends Base
{
    use CanBeContained;
    use CanPagination;
    use WithoutUrlPagination;

    public Collection $products;

    /**
     * 产品详情路由名（如 sn-shop.product.detail）：产品包自身不设路由，
     * 由消费模块定义详情页后传入；缺省时卡片不渲染跳转链接（同 preference 包的 hrefRoute 模式）
     */
    #[Locked]
    public ?string $hrefRoute = null;

    public function mount()
    {
        $this->products = $this->products ?? collect([]);
    }

    protected function getCurrents()
    {
        return $this->products;
    }

    public function render()
    {
        // 内容型排序：order_column desc（新 = 大），与后台默认排序一致
        $query = Utils::getProductModel()::snScope(...$this->getScopeable())
            ->up()
            ->with('media')
            ->orderBy('order_column', 'desc')
            ->orderBy('id', 'desc');

        $this->products = $this->withPagination($query, $this->getFingerprint());

        return view('sn-product::livewire.components.product.products', [
            'paginatorLink' => $this->links,
        ]);
    }

    public function getDetailUrl($product): ?string
    {
        if (blank($this->hrefRoute)) {
            return null;
        }

        return sn_route($this->hrefRoute, ['id' => $product->id]);
    }

    protected function getFingerprint(): string
    {
        return md5(serialize([
            'hrefRoute' => $this->hrefRoute,
            ...$this->getScopeable(),
        ]));
    }
}
