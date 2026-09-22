{{-- 产品卡片网格：行式列表型的分页容器（class 透传 @container，网格列数按容器宽度自适应） --}}
<x-sn-support::paginators.container :page-type="$pageType" :page-info="$pageInfo" :paginator-link="$paginatorLink" :page-name="$pageName" class="@container">
    @php
        $url = fn ($product) => $this->getDetailUrl($product);
    @endphp

    @if ($products->isEmpty())
        <x-sn-support::empty :contained="$contained" icon="{{ \Filament\Support\Icons\Heroicon::OutlinedShoppingBag }}" heading="{{ __('sn-product::product.components.products_empty_heading') }}" description="{{ __('sn-product::product.components.products_empty_description') }}" />
    @else
        {{-- 网格断点按容器宽度：1/2/3/4/5/6 列递进，嵌入窄槽（编排/半宽）时自动降列 --}}
        <div class="w-full grid grid-cols-1 @2xl:grid-cols-2 @3xl:grid-cols-3 @5xl:grid-cols-4 @6xl:grid-cols-5 @7xl:grid-cols-6 gap-2.5">
            @foreach ($products as $product)
                @php
                    $detailUrl = $url($product);
                @endphp
                <div class="sn-container group flex flex-col rounded-md overflow-hidden sn-hover">
                    @if ($detailUrl)
                        <a {{ \Filament\Support\generate_href_html($detailUrl) }} class="w-full flex flex-col grow">
                    @else
                        <div class="w-full flex flex-col grow">
                    @endif

                    <div class="w-full relative pb-[100%] bg-gray-100 dark:bg-gray-800">
                        @if ($product->getFirstMediaUrl('product_image'))
                            <img src="{{ $product->getFirstMediaUrl('product_image') }}" class="size-full object-cover absolute transition duration-700 ease-out group-hover:scale-105" alt="{{ $product->title }}" loading="lazy" />
                        @else
                            <div class="sn-image-placeholder absolute inset-0">
                                <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedPhoto" class="w-10 h-10" aria-hidden="true" />
                            </div>
                        @endif
                    </div>

                    <div class="w-full flex flex-col grow p-2.5 gap-1">
                        <div class="sn-h4-text line-clamp-2">
                            {{ $product->title }}
                        </div>

                        @if (filled($product->subtitle))
                            <div class="sn-descript-text line-clamp-1">
                                {{ $product->subtitle }}
                            </div>
                        @endif

                        <div class="mt-auto pt-1.5">
                            <x-sn-support::amount :amount="$product->price" />
                        </div>
                    </div>

                    @if ($detailUrl)
                        </a>
                    @else
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-sn-support::paginators.container>
