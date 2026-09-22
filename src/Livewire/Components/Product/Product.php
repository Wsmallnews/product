<?php

namespace Wsmallnews\Product\Livewire\Components\Product;

use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Enums\ProductStockType;
use Wsmallnews\Product\Enums\VariantStatus;
use Wsmallnews\Product\Livewire\Components\Base;
use Wsmallnews\Product\Models\Variant;
use Wsmallnews\Product\Support\Utils;
use Wsmallnews\Support\Facades\Seo;
use Wsmallnews\Support\Livewire\Concerns\CanBeContained;
use Wsmallnews\Support\Livewire\Concerns\HasAuth;

class Product extends Base
{
    use CanBeContained;
    use HasAuth;

    /**
     * 产品寻址键：产品包不设路由，由消费模块的路由页传入产品主键
     */
    #[Locked]
    public ?int $id = null;

    /**
     * 购买数量
     */
    public int $productNum = 1;

    /**
     * 已选规格：parent_spec_id => child_spec_id
     *
     * @var array<int, int>
     */
    public array $selectedSpecs = [];

    public function chooseSpec(int $parentId, int $specId): void
    {
        // 再次点击已选规格 = 取消选中
        if (($this->selectedSpecs[$parentId] ?? null) === $specId) {
            unset($this->selectedSpecs[$parentId]);
        } else {
            $this->selectedSpecs[$parentId] = $specId;
        }
    }

    public function render()
    {
        $product = $this->loadProduct();

        // 增加浏览量（未登录时只计数不记录）
        $product->view($this->getAuthUser());

        // 产品页 SEO：标题/描述/封面用产品自身数据
        Seo::title($product->title)
            ->description($product->subtitle)
            ->image($product->getSnSubjectCoverUrl());

        $parentSpecs = $this->getParentSpecs($product);
        $saleableVariants = $this->getSaleableVariants($product);

        // 规格可选状态：其他规格组的当前选择下，某个规格值是否仍能组合出可售变体
        $specSelectable = $this->getSpecSelectable($parentSpecs, $saleableVariants);

        // 已选变体：全部规格组选中后，规格组合与所选一致的可售变体
        $choosedVariant = $this->getChoosedVariant($product, $parentSpecs, $saleableVariants);

        // 轮播图：主图在前，轮播图在后
        $slides = [];
        if ($mainUrl = $product->getFirstMediaUrl('product_image')) {
            $slides[] = ['image' => $mainUrl];
        }
        foreach ($product->getMedia('product_images') as $media) {
            $slides[] = ['image' => $media->getUrl()];
        }

        return view('sn-product::livewire.components.product.product', [
            'product' => $product,
            'parentSpecs' => $parentSpecs,
            'specSelectable' => $specSelectable,
            'choosedVariant' => $choosedVariant,
            'slides' => $slides,
        ]);
    }

    public function buy(): void
    {
        $product = filled($this->id) ? $this->loadProduct(false) : null;

        if (! $product) {
            return;
        }

        $choosedVariant = $this->getChoosedVariant(
            $product,
            $this->getParentSpecs($product),
            $this->getSaleableVariants($product),
        );

        if (! $choosedVariant) {
            Notification::make()
                ->title(__('sn-product::product.components.select_spec_first'))
                ->warning()
                ->send();

            return;
        }

        // 购买事件：产品包不感知交易模块，由消费页面监听后跳转下单流程
        $this->dispatch('product-buy', ...[
            'type' => 'product',
            'from' => 'product-detail',
            'relate_items' => json_encode([
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $choosedVariant->id,
                    'product_num' => $this->productNum,
                    'product_attributes' => [],
                ],
            ]),
        ]);
    }

    /**
     * 加载产品（每次请求重新查询，避免模型序列化导致的关联丢失）
     */
    protected function loadProduct(bool $scopeable = true)
    {
        $query = Utils::getProductModel()::query()
            // 可售状态：上架 + 隐藏（隐藏 = 不列表展示，直达链接可买）
            ->whereIn('status', [ProductStatus::Up, ProductStatus::Hidden])
            ->with([
                'media',
                'content',
                'specs.children',
                'variants.specs',
            ]);

        if ($scopeable) {
            $query->snScope(...$this->getScopeable());
        }

        return $query->findOrFail($this->id);
    }

    /**
     * 规格组（父规格含子项）：单规格产品无规格选择
     */
    protected function getParentSpecs($product): Collection
    {
        if ($product->spec_type === ProductSpecType::Single) {
            return collect([]);
        }

        return $product->parentSpecs;
    }

    /**
     * 可售变体：上架中；库存型商品还需有库存
     */
    protected function getSaleableVariants($product): Collection
    {
        return $product->variants
            ->filter(function (Variant $variant) use ($product) {
                if ($variant->status !== VariantStatus::Up) {
                    return false;
                }

                return $product->stock_type !== ProductStockType::Stock || $variant->stock > 0;
            })
            ->values();
    }

    /**
     * 已选变体：全部规格组均已选择时，规格组合与所选完全一致的可售变体；
     * 单规格产品直接返回唯一可售变体
     */
    protected function getChoosedVariant($product, Collection $parentSpecs, Collection $saleableVariants): ?Variant
    {
        if ($product->spec_type === ProductSpecType::Single) {
            return $saleableVariants->first();
        }

        if ($parentSpecs->isEmpty() || $this->selectedSpecs === []) {
            return null;
        }

        // 每个规格组都已选择，才存在确定组合
        if ($parentSpecs->pluck('id')->diff(array_keys($this->selectedSpecs))->isNotEmpty()) {
            return null;
        }

        $selectedIds = collect(array_values($this->selectedSpecs))->sort()->values()->all();

        return $saleableVariants->first(
            fn (Variant $variant) => $variant->specs->pluck('id')->sort()->values()->all() === $selectedIds
        );
    }

    /**
     * 规格可选状态：当前其他规格组的选择下，某个规格值是否仍能组合出可售变体
     *
     * @return array<int, array<int, bool>> parent_spec_id => [child_spec_id => selectable]
     */
    protected function getSpecSelectable(Collection $parentSpecs, Collection $saleableVariants): array
    {
        $selectable = [];

        foreach ($parentSpecs as $parentSpec) {
            foreach ($parentSpec->children as $child) {
                // 排除该规格组自身的选择，只看其他规格组的约束
                $constraints = collect($this->selectedSpecs)->except($parentSpec->id);

                $match = $saleableVariants->contains(function (Variant $variant) use ($child, $constraints) {
                    $variantSpecIds = $variant->specs->pluck('id');

                    // 目标规格值在组合中，其余所选规格值也都在组合中
                    return $variantSpecIds->contains($child->id)
                        && $constraints->every(fn (int $specId) => $variantSpecIds->contains($specId));
                });

                $selectable[$parentSpec->id][$child->id] = $match;
            }
        }

        return $selectable;
    }
}
