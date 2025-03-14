@props([
    'product',
    'detailRouteName',
])

@php
    use Wsmallnews\Product\Product;
    $url = route($detailRouteName, ['id' => $product->id]);
@endphp

<x-sn-support::base.card class="w-full group flex rounded-md flex-col overflow-hidden" tag="a" :border="false" :href="$url" :should-open-url-in-new-tab="true" >
    <div class="w-full relative pb-[100%]">
        @if (filled($product->image))
            <img src="{{ Product::filesUrl($product->image) }}" class="size-full object-cover absolute transition duration-700 ease-out group-hover:scale-105" />
        @endif
    </div>

    <div class="flex flex-col p-2.5">
        <div class="h-12 text-base line-clamp-2 font-medium">
            {{ $product->title }}
        </div>
        <div class="h-8.5 mt-1 text-sm line-clamp-2">
            {{ $product->subtitle }}
        </div>

        <div class="flex items-end mt-2.5">
            <x-sn-support::amount :amount="$product->price" />
            <span class="text-sm ml-1.5 leading-5">{{ $product->sales }}+人购买</span>
        </div>
    </div>
</x-sn-support::base.card>