{{-- class 透传到 paginators.container 根元素（w-full @container）：卡片网格用容器断点自适应实际宽度，不依赖外部容器祖先 --}}
<x-sn-support::paginators.container :page-type="$pageType" :page-info="$pageInfo" :paginator-link="$paginatorLink" :page-name="$pageName" class="@container">
    <div class="w-full grid grid-cols-1 @2xl:grid-cols-2 @3xl:grid-cols-3 @5xl:grid-cols-4 @6xl:grid-cols-5 @7xl:grid-cols-6 gap-2.5">
        @foreach($products as $product)
            <x-dynamic-component :component="$cardView" key="product-{{$product->id}}" :detail-route-name="$detailRouteName" :product="$product" />
        @endforeach
    </div>
</x-sn-support::paginators.container>