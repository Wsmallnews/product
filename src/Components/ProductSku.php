<?php

namespace Wsmallnews\Product\Components;

use Illuminate\Support\Collection;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Models\SkuPrice;
use Wsmallnews\Support\Components\BaseComponent;

class ProductSku extends BaseComponent
{

    public Product $product;

    // 展示的 skus
    public array $showSkus = [];

    // 当前选中的 skuPrice
    public ?SkuPrice $choosedSkuPrice = null;

    // 当前已选择的skus
    public array $currentChoosedSkus = [];

    protected Collection $skus;
    protected Collection $skuPrices;

    public function boot()
    {
        // 这里使用 mount 不行，
        // 1、后续请求 product 会被重新查询，并且不会加载关系，就需要在boot 手动加载 关系
        // 2、如果在 把  skus 和 skuPrices 设置成 public 也不行，后续请求的 skus 不会自动关联 children
        // 
        $this->product->loadMissing('skus.children');
        $this->product->loadMissing('skuPrices');

        $this->skus = $this->product->skus;
        $this->skuPrices = $this->product->skuPrices;
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

        $canUseSkuPrices = $this->getCanUseSkuPrices(); // 获取当前所选的所有 skuPrice
        if (count($this->currentChoosedSkus) == $this->skus->count() && $canUseSkuPrices->isNotEmpty()) {
            $this->choosedSkuPrice = $canUseSkuPrices->first(); // 选中第一个
        } else {
            $this->choosedSkuPrice = null;
        }

        if ($this->choosedSkuPrice) {
            $this->dispatch('choosed-sku-price', skuPriceId: $this->choosedSkuPrice->id);
        }

        $this->operShowSkus($isChecked, $parentSkuId, $skuId);
    }



    public function checkIsChoosed($parentId, $id): bool
    {
        return isset($this->currentChoosedSkus[$parentId]) && $this->currentChoosedSkus[$parentId] == $id;
    }


    /**
     * 当前所选规格下，获取所有有库存的 skuPrice
     */
    protected function getCanUseSkuPrices ()
    {
        $canUseSkuPrices = collect([]);
        foreach ($this->skuPrices as $skuPrice) {
            if ($this->product->stock_type == 'stock' && $skuPrice->stock <= 0) {        // 商品控制库存，并且库存小于 0
                continue;
            }

            $isOk = true;
            foreach ($this->currentChoosedSkus as $parentId => $sku) {
                if (!in_array($sku, $skuPrice->product_sku_ids)) {      // 没有被选中
                    $isOk = false;
                }
            }

            if ($isOk) {
                $canUseSkuPrices->push($skuPrice);
            }
        }

        return $canUseSkuPrices;
    }



    protected function operShowSkus($isChecked, $parentSkuId = 0, $skuId = 0)
    {
        $canUseSkuPrices = collect([]);  // 所有可以选择的 skuPrice
        if ($isChecked) {
            foreach ($this->skuPrices as $skuPrice) {
                if ($this->product->stock_type == 'stock' && $skuPrice->stock <= 0) {        // 商品控制库存，并且库存小于 0
                    continue;
                }
                if (in_array($skuId, $skuPrice->product_sku_ids)) {
                    $canUseSkuPrices->push($skuPrice);
                }
            }
        } else {
            // 当前所选规格下，所有可以选择的 skuPrice
            $canUseSkuPrices = $this->getCanUseSkuPrices();
        }

        $noChooseSkuIds = []; // 所有可以选择的 skuPrice 的 id
        foreach ($canUseSkuPrices as $skuPrice) {
            $noChooseSkuIds = array_merge($noChooseSkuIds, $skuPrice->product_sku_ids);
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
