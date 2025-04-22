<?php

namespace Wsmallnews\Product\Components;

use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Wsmallnews\Product\Enums;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Models\SkuPrice;
use Wsmallnews\Support\Components\BaseComponent;

class ProductDetail extends BaseComponent
{
    public Product $product;

    public ?SkuPrice $choosedSkuPrice = null;

    public int $productNum = 1;

    public function mount($id)
    {
        $query = Product::query()->scopeable(...$this->getScopeInfo())->show()->with([
            'skus.children',
            'attributes' => function ($builder) {
                $builder->with(['children.attribute_repository', 'attribute_repository']);
            }
        ]);

        $this->product = $query->findOrFail($id);

        if ($this->product->sku_type == Enums\ProductSkuType::Single) {
            $this->choosedSkuPrice = $this->product->skuPrice;
        }
    }


    public function buy()
    {
        if (!$this->choosedSkuPrice) {
            Notification::make()
                ->title('请选择规格')
                ->danger()
                ->send();
            return;
        }

        $this->dispatch('product-buy', ...[
            'type' => 'product',
            'from' => 'product-detail',
            'relate_items' => json_encode([
                [
                    'product_id' => $this->product->id,
                    'product_sku_price_id' => $this->choosedSkuPrice->id,
                    'product_num' => $this->productNum,
                    'product_attributes' => [],
                ]
            ])
        ]);
    }


    #[On('choosed-sku-price')]
    public function choosedSkuPrice($skuPriceId)
    {
        $this->choosedSkuPrice = $this->product->skuPrices->firstWhere('id', $skuPriceId);
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
