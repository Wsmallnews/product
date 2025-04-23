@php
    $assetsUrl = config('filesystems.disks.' . config('filament.default_filesystem_disk') . '.url') . '/';
@endphp

<div class="w-full overflow-hidden pt-4" x-data="skuManager({
    product: @js($product),
    skus: @js($skus),
    variants: @js($variants)
})">
    <template x-for="sku in skus">
        <div class="flex">
            <div class="mr-3 flex-none" x-text="sku.name"></div>
            <div class="flex flex-wrap grow">
                <template x-for="child in sku.children">
                    <button class="flex grow-0 shrink-0 basis-auto items-center mr-3 mb-3 rounded-md disabled:opacity-50 ring-1" :class="{
                        'bg-primary-600 text-white ring-primary-600': currentSkuArray[child.parent_id] == child.id,
                        'ring-gray-300 hover:ring-primary-600': currentSkuArray[child.parent_id] != child.id,
                    }" :disabled="child.disabled"  @click="onSelectSku(sku.id, child.id)">
                        <template x-if="child.image">
                            <div class="w-7 h-7 overflow-hidden rounded-md">
                                <img class="w-full h-full object-contain" :src="'{{$assetsUrl}}' + child.image" :alt="child.name"/>
                            </div>
                        </template>
                        <div class="flex-1 truncate px-3 py-1" x-text="child.name"></div>
                    </button>
                </template>
            </div>
        </div>
    </template>
</div>

@assets
<script>
function skuManager({
    product,
    skus,
    variants
}) {
    return {
        product,
        skus: [],
        variants: [],
        currentSkuArray: [],
        selectedVariant: {},
        init () {
            this.skus = skus;
            console.log(this.skus, 'this.skus');
            this.variants = variants;

            this.$watch('selectedVariant', (newVal, oldVal) => {
                this.$dispatch('sku-choosed', {variant: newVal});
            });

            this.changeDisabled(false);
        },
        onSelectSku (pId, skuId) {      // 选择规格
            // 清空已选择
            let isChecked = true; // 选中 or 取消选中
            if (this.currentSkuArray[pId] != undefined && this.currentSkuArray[pId] == skuId) {
                // 点击已被选中的，删除并填充 ''
                isChecked = false;
                this.currentSkuArray.splice(pId, 1, '');
            } else {
                // 选中
                this.currentSkuArray[pId] = skuId;
            }

            let chooseSkuId = []; // 选中的规格大类
            this.currentSkuArray.forEach((sku) => {
                if (sku.toString() != '') {
                    // sku 为空是反选 填充的
                    chooseSkuId.push(sku);
                }
            });

            // 当前所选规格下，所有可以选择的 variant
            let canVariants = this.getCanUseVariants();

            // 判断所有规格大类是否选择完成
            if (chooseSkuId.length == this.skus.length && canVariants.length) {
                canVariants[0].product_num = this.selectedVariant.product_num || 1;       // 要购买的数量
                this.selectedVariant = canVariants[0];
            } else {
                this.selectedVariant = {};
            }

            // 改变规格项禁用状态
            this.changeDisabled(isChecked, pId, skuId);
        },
        getCanUseVariants () {          // 当前所选规格下，获取所有有库存的 variant
            let newVariants = [];

            for (let variant of this.variants) {
                if (this.product.stock_type == 'stock' && variant.stock <= 0) {        // 商品控制库存，并且库存小于 0
                    // || price.stock < this.goodsNum		不判断是否大于当前选择数量，在 uni-number-box 判断，并且 取 stock 和 goods_num 的小值
                    continue;
                }
                var isOk = true;

                this.currentSkuArray.forEach((sku) => {
                    // sku 不为空，并且，这个 条 variant 没有被选中,则排除
                    if (sku.toString() != '' && variant.product_sku_ids.indexOf(sku.toString()) < 0) {
                        isOk = false;
                    }
                });

                if (isOk) {
                    newVariants.push(variant);
                }
            }

            return newVariants;
        },
        changeDisabled(isChecked = false, pId = 0, skuId = 0) {         // 改变禁用状态
            let newVariants = []; // 所有可以选择的 variant

            if (isChecked) {                            // 选中规格
                // 当前点击选中规格下的 所有可用 variant
                for (let variant of this.variants) {
                    if (this.product.stock_type == 'stock' && variant.stock <= 0) {
                        // this.goodsNum 不判断是否大于当前选择数量，在 uni-number-box 判断，并且 取 stock 和 goods_num 的小值
                        continue;
                    }
                    if (variant.product_sku_ids.indexOf(skuId.toString()) >= 0) {
                        newVariants.push(variant);
                    }
                }
            } else {                                    // 取消选中
                // 当前所选规格下，所有可以选择的 variant
                newVariants = this.getCanUseVariants();
            }

            // 所有存在并且有库存未选择的规格项 的 子项 id
            let noChooseSkuIds = [];
            for (let newVariant of newVariants) {
                noChooseSkuIds = noChooseSkuIds.concat(newVariant.product_sku_ids);
            }

            // 去重
            noChooseSkuIds = Array.from(new Set(noChooseSkuIds));

            if (isChecked) {
                // 去除当前选中的规格项
                let index = noChooseSkuIds.indexOf(skuId.toString());
                noChooseSkuIds.splice(index, 1);
            } else {
                // 循环去除当前已选择的规格项
                this.currentSkuArray.forEach((sku) => {
                    if (sku.toString() != '') {
                        // sku 为空是反选 填充的
                        let index = noChooseSkuIds.indexOf(sku.toString());
                        if (index >= 0) {
                            // sku 存在于 noChooseSkuIds
                            noChooseSkuIds.splice(index, 1);
                        }
                    }
                });
            }

            // 当前已选择的规格大类
            let chooseSkuKey = [];
            if (!isChecked) {
                // 当前已选择的规格大类
                this.currentSkuArray.forEach((sku, key) => {
                    if (sku.toString() != '') {
                        // sku 为空是反选 填充的
                        chooseSkuKey.push(key);
                    }
                });
            } else {
                // 当前点击选择的规格大类
                chooseSkuKey = [pId];
            }

            for (let i in this.skus) {
                // 当前点击的规格，或者取消选择时候 已选中的规格 不进行处理
                if (chooseSkuKey.indexOf(this.skus[i]['id']) >= 0) {
                    continue;
                }

                for (let j in this.skus[i]['children']) {
                    // 如果当前规格项 id 不存在于有库存的规格项中，则禁用
                    if (noChooseSkuIds.indexOf(this.skus[i]['children'][j]['id'].toString()) >= 0) {
                        this.skus[i]['children'][j]['disabled'] = false;
                    } else {
                        this.skus[i]['children'][j]['disabled'] = true;
                    }
                }
            }
        }
    }
}
</script>
@endassets