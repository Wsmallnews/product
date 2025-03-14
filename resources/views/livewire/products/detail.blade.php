@php
use Wsmallnews\Product\Product;
@endphp

@assets
<style>
    .product-content img {
        width: 100%;
    }
</style>
@endassets

<div class="w-full" x-data="detailManager({
    product: @js($product),
    skus: @js($skus),
    skuPrices: @js($skuPrices)
})">

    <div class="w-full grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-white">
        <div class="w-full">
            <x-sn-support::swiper :images="Product::filesUrl($product->images)" class="w-full" :has-thumb="true" :thumb-scale="20" thumb-position="left" />
        </div>

        <div class="w-full flex flex-col" @sku-choosed="skuChoosed">
            <div class="text-xl font-bold">{{$product->title}}</div>
            <div class="text-base text-gray-600">{{$product->subtitle}}</div>

            {{-- <x-sn-support::amount  /> --}}

            <livewire:sn-product-sku :product="$product" />

            <div class="" @click="buy">
                立即购买
            </div>
        </div>
    </div>

    <div class="w-full grid grid-cols-1 bg-white p-4" >
        <x-sn-support::tabs label="Content tabs" :contained="true" class="bg-white sticky top-0">
            <x-sn-support::tabs.item tag="a" href="#product-evaluate" alpine-active="currentTab == 'product-evaluate'">
                用户评价
            </x-sn-support::tabs.item>

            <x-sn-support::tabs.item tag="a" href="#product-params" alpine-active="currentTab == 'product-params'">
                参数信息
            </x-sn-support::tabs.item>

            <x-sn-support::tabs.item tag="a" href="#product-content" alpine-active="currentTab == 'product-content'">
                图文详情
            </x-sn-support::tabs.item>
        </x-sn-support::tabs>

        <div class="w-full lg:w-2/3">
            <div class="w-full">
                <div id="product-evaluate" class="tab-content text-lg font-bold my-4 scroll-mt-16">用户评价</div>
                <div class="h-[500px]"></div>
            </div>

            @if($product->params)
                <div class="w-full">
                    <div id="product-params" class="tab-content text-lg font-bold my-4 scroll-mt-16">参数信息</div>
                    <div class="w-full flex flex-row flex-wrap overflow-hidden border-t border-l border-gray-200">
                        @foreach($product->params as $key => $param)
                            <div class="w-1/2 min-h-14 flex flex-row justify-start items-center border-r border-b border-gray-200">
                                <div class="w-2/5 h-full flex items-center bg-gray-200 px-4">{{$key}}</div>
                                <div class="w-3/5 h-full flex items-center px-4">{{$param}}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($product->content)
                <div class="w-full">
                    <div id="product-content" class="tab-content text-lg font-bold my-4 scroll-mt-16">图文详情</div>
                    <div class="product-content w-full">{!! $product->content !!}</div>
                </div>
            @endif
        </div>

    </div>
</div>


@assets
<script>
    function detailManager({
        product,
        skus,
        skuPrices
    }) {
        return {
            product,
            skus,
            skuPrices,
            currentSkuPrice: {},
            currentTab: null,
            init () {
                if (this.product.sku_type == 'single') {
                    // 单规格
                    this.currentSkuPrice = this.skuPrices[0];
                }

                // 监听滚动条
                window.addEventListener('scroll', () => {
                    const tabContents = document.querySelectorAll('.tab-content');
                    tabContents.forEach(tabContent => {
                        const id = tabContent.getAttribute('id');
                        const rect = tabContent.getBoundingClientRect();

                        if (rect.top <= 100 && rect.bottom >= 100) {
                            this.currentTab = id;
                        }
                    });
                });
            },
            skuChoosed (e) {
                console.log(e, 'skuChoosed');

                let data = e.detail;
                this.currentSkuPrice = data.skuPrice;
            },
            buy () {
                // 检查用户是否选择了该选的东西
                if (Object.keys(this.currentSkuPrice).length === 0) {
                    // 提示
                    new FilamentNotification()
                        .title('请选择规格')
                        .danger()
                        .send()

                    return false;
                }

                // set 设置 buyInfo 会触发一次 livewire/update 请求
                this.$wire.set('buyInfo', {
                    product_id: this.product.id,
                    product_sku_price_id: this.currentSkuPrice.id,
                    product_num: 1,
                    product_attributes: []
                });

                this.$wire.dispatch('product-buy', {
                    type: 'product',
                    from: 'product-detail',
                    relate_items: JSON.stringify([this.$wire.buyInfo]),
                });


                // 这个写法不会触发 livewire/update，但是相关数据都不会更新了
                // this.$wire.dispatch('product-buy', {
                //     type: 'product',
                //     from: 'product-detail',
                //         relate_items: JSON.stringify([{
                //         product_id: this.product.id,
                //         product_sku_price_id: this.currentSkuPrice.id,
                //         product_num: 1,
                //         product_attributes: []
                //     }]),
                // });

                // this.$wire.buy();
            }
        }
    }
</script>
@endassets