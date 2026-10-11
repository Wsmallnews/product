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
use Wsmallnews\Product\Support\ExtraManager;
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

        // 规格选择契约：PC 前端（Alpine）与将来 uniapp 端消费同一份 schema，
        // 选择/计价在客户端本地完成，服务端 buy()/下单管道始终按 DB 重新校验
        $specSchema = $this->getSpecSchema($product, $parentSpecs, $saleableVariants);

        // 价格初始展示件（SEO / Alpine 加载前的回退）：单规格取首个可售变体价，多规格取 SPU 价
        $initialVariant = $product->spec_type === ProductSpecType::Single ? $saleableVariants->first() : null;
        $initialPriceParts = sn_money()->formatParts($initialVariant?->price ?? $product->price);

        // 轮播图：主图在前，轮播图在后
        $slides = [];
        $mainImageUrl = null;
        if ($mainUrl = $product->getFirstMediaUrl('product_image')) {
            $mainImageUrl = $mainUrl;
            $slides[] = ['image' => $mainUrl];
        }
        foreach ($product->getMedia('product_images') as $media) {
            $slides[] = ['image' => $media->getUrl()];
        }

        return view('sn-product::livewire.components.product.product', [
            'product' => $product,
            'parentSpecs' => $parentSpecs,
            'specSchema' => $specSchema,
            'initialPriceParts' => $initialPriceParts,
            'initialPriceMinor' => (int) ($initialVariant?->price ?? $product->price)->getAmount(),
            'mainImageUrl' => $mainImageUrl,
            'slides' => $slides,
        ]);
    }

    /**
     * 立即购买：服务端只校验变体归属与可售状态（可售变体集合内匹配），
     * 前端本地选中的展示态不作为信任来源
     */
    public function buy(int $variantId = 0, int $productNum = 0, ?array $extras = null): void
    {
        $product = filled($this->id) ? $this->loadProduct(false) : null;

        if (! $product) {
            return;
        }

        $choosedVariant = $this->getSaleableVariants($product)
            ->first(fn (Variant $variant) => $variant->id === $variantId);

        if (! $choosedVariant) {
            Notification::make()
                ->title(__('sn-product::product.components.select_spec_first'))
                ->warning()
                ->send();

            return;
        }

        // 购买事件：产品包不感知交易模块，由消费页面监听后跳转下单流程
        $this->dispatch('sn-product-buy', ...[
            'type' => 'product',
            'from' => 'product-detail',
            'relate_items' => json_encode([
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $choosedVariant->id,
                    'product_num' => max(1, $productNum),
                    'extras' => array_values((array) $extras),
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
                'extraItems.extra.parent',
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
     * 规格选择 schema（版本化契约）：PC Alpine 与 uniapp 接口共用同一结构
     *
     * @return array{
     *     schema_version: int,
     *     spec_type: string,
     *     specs: list<array{id: int, name: string, children: list<array{id: int, name: string, image: ?string}>}>,
     *     variants: list<array{id: int, spec_ids: list<int>, spec_text: list<string>, price: int, price_parts: array{symbol: string, amount: string}, original_price_parts: ?array{symbol: string, amount: string}, image: ?string, stock: ?int}>,
     *     addons: list<mixed>
     * }
     */
    protected function getSpecSchema($product, Collection $parentSpecs, Collection $saleableVariants): array
    {
        return [
            'schema_version' => 1,
            'spec_type' => $product->spec_type->value,
            'specs' => $parentSpecs->map(fn ($parentSpec) => [
                'id' => (int) $parentSpec->id,
                'name' => (string) $parentSpec->name,
                'children' => $parentSpec->children->map(fn ($child) => [
                    'id' => (int) $child->id,
                    'name' => (string) $child->name,
                    'image' => $child->image ?: null,
                ])->values()->all(),
            ])->values()->all(),
            'variants' => $saleableVariants->map(fn (Variant $variant) => [
                'id' => (int) $variant->id,
                'spec_ids' => $variant->specs->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'spec_text' => $variant->product_spec_text,
                'price' => (int) $variant->price->getAmount(),
                'price_parts' => sn_money()->formatParts($variant->price),
                'original_price_parts' => filled($variant->original_price) ? sn_money()->formatParts($variant->original_price) : null,
                // 组合图：变体覆盖图优先（后台可精确指定），未指定由前端按「首个带图规格组选中值」推导
                'image' => $variant->image ?: null,
                // 非库存型商品不限量：stock 输出 null（前端低库存提示只对库存型生效）
                'stock' => $product->stock_type === ProductStockType::Stock ? $variant->stock : null,
            ])->values()->all(),
            // 附加项（加料/增值服务）：ExtraManager 统一解析（有效价/售罄标记），与下单管道同源
            'extras' => collect(app(ExtraManager::class)->resolveProductExtras($product))->map(function (array $group) {
                $group['options'] = collect($group['children'])->map(function (array $option) {
                    $option['price_parts'] = sn_money()->formatParts(sn_money()->fromMinor($option['price']));

                    return $option;
                })->all();
                unset($group['children']);

                return $group;
            })->values()->all(),
        ];
    }
}
