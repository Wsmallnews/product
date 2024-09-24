@php
    $record = $getRecord();
@endphp

{{--
<div class="flex p-4 min-w-80">
    <img class="w-20 h-20 rounded-lg" src="{{config('filesystems.disks.' . config('filament.default_filesystem_disk') . '.url') . '/' . $record->image}}" alt="">

    <div class="flex flex-col ml-4">
        <span class="font-medium text-ellipsis">{{ $record->title }}</span>
        <span class="text-slate-400 text-sm">{{ $record->subtitle }}</span>
    </div>
</div> --}}


<div class="flex p-3 min-w-80 max-w-96" >
    <img class="size-24 rounded-lg" src="{{config('filesystems.disks.' . config('filament.default_filesystem_disk') . '.url') . '/' . $record->image}}" alt="">

    <div class="ml-3 flex-1 min-w-0">
        <div class="font-medium truncate mb-1">{{ $record->title }}</div>
        <div class="line-clamp-2 text-slate-400 text-sm">{{ $record->subtitle }}</div>
    </div>
</div>