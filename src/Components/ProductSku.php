<?php

namespace Wsmallnews\Product\Components;

use Illuminate\Support\Collection;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Models\Variant;
use Wsmallnews\Support\Components\BaseComponent;

class ProductSku extends BaseComponent
{

    public Product $product;

    // 展示的 skus
    public array $showSkus = [];

    // 当前选中的 variant
    public ?Variant $choosedVariant = null;

    // 当前已选择的skus
    public array $currentChoosedSkus = [];

    protected Collection $skus;
    protected Collection $variants;

    public function boot()
    {
        // 这里使用 mount 不行，
        // 1、后续请求 product 会被重新查询，并且不会加载关系，就需要在boot 手动加载 关系
        // 2、如果在 把  skus 和 variants 设置成 public 也不行，后续请求的 skus 不会自动关联 children
        // 
        $this->product->loadMissing('skus.children');
        $this->product->loadMissing('variants');

        $this->skus = $this->product->skus;
        $this->variants = $this->product->variants;
    }


    public function mount()
    {
        $this->showSkus = $this->skus->toArray();

        $this->operShowSkus(false);
    }


    public function choose($parentSkuId, $skuId)
    {
        $isChecked = true; // 选中 or 取消选中
        if (isset($this->currentChoosedSkus[$parentSkuId]) && $this->currentChoosedSkus[$parentSkuId] == $skuId) {
            // 点击已被选中的，删除选中
            $isChecked = false;
            unset($this->currentChoosedSkus[$parentSkuId]);
        } else {
            // 选中
            $this->currentChoosedSkus[$parentSkuId] = $skuId;
        }

        $canUseVariants = $this->getCanUseVariants(); // 获取当前所选的所有 variants
        if (count($this->currentChoosedSkus) == $this->skus->count() && $canUseVariants->isNotEmpty()) {
            $this->choosedVariant = $canUseVariants->first(); // 选中第一个
        } else {
            $this->choosedVariant = null;
        }

        if ($this->choosedVariant) {
            $this->dispatch('choosed-variant', variantId: $this->choosedVariant->id);
        }

        $this->operShowSkus($isChecked, $parentSkuId, $skuId);
    }



    public function checkIsChoosed($parentId, $id): bool
    {
        return isset($this->currentChoosedSkus[$parentId]) && $this->currentChoosedSkus[$parentId] == $id;
    }


    /**
     * 当前所选规格下，获取所有有库存的 variants
     */
    protected function getCanUseVariants ()
    {
        $canUseVariants = collect([]);
        foreach ($this->variants as $variant) {
            if ($this->product->stock_type == 'stock' && $variant->stock <= 0) {        // 商品控制库存，并且库存小于 0
                continue;
            }

            $isOk = true;
            foreach ($this->currentChoosedSkus as $parentId => $sku) {
                if (!in_array($sku, $variant->product_sku_ids)) {      // 没有被选中
                    $isOk = false;
                }
            }

            if ($isOk) {
                $canUseVariants->push($variant);
            }
        }

        return $canUseVariants;
    }



    protected function operShowSkus($isChecked, $parentSkuId = 0, $skuId = 0)
    {
        $canUseVariants = collect([]);  // 所有可以选择的 variants
        if ($isChecked) {
            foreach ($this->variants as $variant) {
                if ($this->product->stock_type == 'stock' && $variant->stock <= 0) {        // 商品控制库存，并且库存小于 0
                    continue;
                }
                if (in_array($skuId, $variant->product_sku_ids)) {
                    $canUseVariants->push($variant);
                }
            }
        } else {
            // 当前所选规格下，所有可以选择的 variants
            $canUseVariants = $this->getCanUseVariants();
        }

        $noChooseSkuIds = []; // 所有可以选择的 variants 的 id
        foreach ($canUseVariants as $canUsevariant) {
            $noChooseSkuIds = array_merge($noChooseSkuIds, $canUsevariant->product_sku_ids);
        }
        // 去重
        $noChooseSkuIds = array_values(array_filter(array_unique($noChooseSkuIds)));

        if ($isChecked) {
            // 去除当前选中的规格项
            $index = array_search($skuId, $noChooseSkuIds);
            if ($index !== false) {
                unset($noChooseSkuIds[$index]);
            }
        } else {
            // 循环去除当前已选择的规格项
            foreach ($this->currentChoosedSkus as $choosedSku) {
                if ($choosedSku != $skuId) {
                    // sku 为空是反选 填充的
                    $index = array_search($choosedSku, $noChooseSkuIds);
                    if ($index !== false) {
                        unset($noChooseSkuIds[$index]);
                    }
                }
            }
        }

        if (!$isChecked) {
            // 当前已选择的规格大类
            $chooseParentskuIds = array_keys($this->currentChoosedSkus);
        } else {
            // 当前点击选择的规格大类
            $chooseParentskuIds = [$parentSkuId];
        }

        foreach ($this->skus as $pk => $sku) {
            // 当前点击的规格，或者取消选择时候 已选中的规格 不进行处理
            if (in_array($sku->id, $chooseParentskuIds)) {
                continue;
            }

            foreach ($sku->children as $ck => $child) {
                // 如果当前规格项 id 不存在于有库存的规格项中，则禁用
                if (in_array($child->id, $noChooseSkuIds)) {
                    $this->showSkus[$pk]['children'][$ck]['disabled'] = false;
                } else {
                    $this->showSkus[$pk]['children'][$ck]['disabled'] = true;
                }
            }
        }
    }


    public function render()
    {
        return view('sn-product::livewire.products.sku')->title('产品详情');
    }
}
