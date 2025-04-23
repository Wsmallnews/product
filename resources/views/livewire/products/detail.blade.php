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
    product: @js($product)
})">
    <div class="w-full grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-white">
        <div class="w-full">
            <x-sn-support::swiper :images="$product->galleryUrls" class="w-full" :has-thumb="true" :thumb-scale="20" thumb-position="left" />
        </div>

        <div class="w-full flex flex-col gap-y-4">
            <div class="flex flex-col gap-y-1">
                <div class="text-xl font-bold">{{$product->title}}</div>
                <div class="text-base text-gray-600">{{$product->subtitle}}</div>
            </div>

            <x-sn-support::amount :amount="$choosedVariant ? $choosedVariant->price : $product->price" />

            {{-- 选择规格 --}}
            <livewire:sn-product-sku :product="$product" />

            {{-- 数量 --}}
            <div class="flex items-center">
                <div class="w-16 mr-4 text-sm text-gray-600 shrink-0 grow-0">数量</div>
                <div class="w-32 shrink-0 grow-0">
                    <x-sn-support::input.step-number :min="1" wire:model="productNum" />
                </div>
            </div>

            <x-filament::button wire:click="buy" size="xl" class="w-48">
                立即购买
            </x-filament::button>
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
    }) {
        return {
            product,
            currentTab: null,
            init () {
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
        }
    }
</script>
@endassets