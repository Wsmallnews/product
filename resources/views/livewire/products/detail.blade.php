@php
use Wsmallnews\Product\Product;

$slides = [];
foreach ($product->getMedia('product_image') as $media) {
    $slides[] = ['image' => $media->getUrl()];
}
@endphp

<div class="w-full flex flex-col sn-gap" x-data="detailManager({
    product: @js($product)
})">
    <div class="w-full sn-container sn-padded grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
        <div class="w-full">
            <x-sn-support::swiper :slides="$slides" class="w-full" ratio="1/1" :has-thumb="true" thumb-position="left" :thumb-size="72" />
        </div>

        <div class="w-full flex flex-col gap-y-4">
            <div class="flex flex-col gap-y-1">
                <div class="sn-h2-text">{{$product->title}}</div>
                <div class="sn-descript-text text-base">{{$product->subtitle}}</div>
            </div>

            <x-sn-support::amount :amount="$choosedVariant ? $choosedVariant->price : $product->price" />

            {{-- 选择规格 --}}
            <livewire:sn-product-sku :product="$product" />

            {{-- 数量 --}}
            <div class="flex items-center">
                <div class="w-16 mr-4 sn-content-text shrink-0 grow-0">数量</div>
                <div class="w-40 shrink-0 grow-0">
                    <x-sn-support::input.step-number :min="1" wire:model="productNum" />
                </div>
            </div>

            <x-filament::button wire:click="buy" size="xl" class="w-48">
                立即购买
            </x-filament::button>
        </div>
    </div>

    <div class="w-full sn-container sn-padded grid grid-cols-1" >
        <x-sn-support::tabs label="Content tabs" :contained="true" class="sticky top-0">
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
                <div id="product-evaluate" class="tab-content sn-h3-text sn-my scroll-mt-16">用户评价</div>
                <div class="h-[500px]"></div>
            </div>

            @if($product->params)
                <div class="w-full">
                    <div id="product-params" class="tab-content sn-h3-text sn-my scroll-mt-16">参数信息</div>
                    <div class="w-full flex flex-row flex-wrap overflow-hidden sn-rounded border-t border-l border-gray-200 dark:border-gray-700">
                        @foreach($product->params as $key => $param)
                            <div class="w-full sm:w-1/2 min-h-14 flex flex-row justify-start items-center border-r border-b border-gray-200 dark:border-gray-700">
                                <div class="w-2/5 h-full flex items-center bg-gray-100 dark:bg-gray-800 sn-px sn-content-text">{{$key}}</div>
                                <div class="w-3/5 h-full flex items-center sn-px sn-content-text">{{$param}}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($product->content)
                <div class="w-full">
                    <div id="product-content" class="tab-content sn-h3-text sn-my scroll-mt-16">图文详情</div>
                    <div class="w-full [&_img]:w-full">{!! $product->content !!}</div>
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
