<?php

namespace Wsmallnews\Product\Components;

use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Wsmallnews\Product\Enums;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Models\Variant;
use Wsmallnews\Support\Components\BaseComponent;

class ProductDetail extends BaseComponent
{
    public Product $product;

    public ?Variant $choosedVariant = null;

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
            $this->choosedVariant = $this->product->variant;
        }
    }


    public function buy()
    {
        if (!$this->choosedVariant) {
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
                    'product_variant_id' => $this->choosedVariant->id,
                    'product_num' => $this->productNum,
                    'product_attributes' => [],
                ]
            ])
        ]);
    }


    #[On('choosed-variant')]
    public function choosedVariant($variantId)
    {
        $this->choosedVariant = $this->product->variants->firstWhere('id', $variantId);
    }


    public function render()
    {
        return view('sn-product::livewire.products.detail', [
            'product' => $this->product,
        ])->title('产品详情');
    }
}
