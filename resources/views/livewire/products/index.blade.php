<x-sn-support::paginators.container :page-type="$pageType" :page-info="$pageInfo" :paginator-link="$paginatorLink" :page-name="$pageName">
    <div class="w-full grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2.5">
        @foreach($products as $product)
            <x-dynamic-component :component="$cardView" key="product-{{$product->id}}" :detail-route-name="$detailRouteName" :product="$product" />
        @endforeach
    </div>
</x-sn-support::paginators.container>