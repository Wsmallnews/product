@php
    $record = $getRecord();
@endphp

<div 
    class="flex p-3 min-w-80 max-w-96"
    x-data="{}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('product-styles', 'wsmallnews/product'))]"
>
    <img class="size-24 rounded-lg" src="{{config('filesystems.disks.' . config('filament.default_filesystem_disk') . '.url') . '/' . $record->image}}" alt="">

    <div class="ml-3 flex-1 min-w-0">
        <div class="font-medium truncate mb-1">{{ $record->title }}</div>
        <div class="line-clamp-2 text-slate-400 text-sm">{{ $record->subtitle }}</div>
    </div>
</div>