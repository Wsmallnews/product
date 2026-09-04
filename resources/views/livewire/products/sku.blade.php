<div class="flex flex-col w-full gap-y-4">
    @foreach ($this->showSkus as $sku)
        <div class="flex flex-wrap items-start gap-x-4 gap-y-2">
            <div class="w-16 shrink-0 grow-0 sn-content-text py-2">{{ $sku['name'] }}</div>
            <div class="flex flex-wrap gap-2 sm:gap-4">
                @foreach ($sku['children'] as $child)
                    <button @class([
                            'flex items-center max-w-full grow-0 shrink-0 basis-auto rounded-(--sn-radius-control) disabled:opacity-50 sn-transition-colors',
                            'bg-primary-600 text-white' => $this->checkIsChoosed($sku['id'], $child['id']),
                            'sn-contour text-gray-700 dark:text-gray-200' => !$this->checkIsChoosed($sku['id'], $child['id']),
                        ])
                        {{ $child['disabled'] ? 'disabled' : '' }}
                        wire:click="choose({{$sku['id']}}, {{$child['id']}})"
                    >
                        @if ($child['image'])
                            <div class="w-7 h-7 overflow-hidden rounded-md">
                                <img class="w-full h-full object-contain" :src="{{$child['image']}}" :alt="{{ $child['name'] }}"/>
                            </div>
                        @endif
                        <div class="truncate px-3 py-1">{{ $child['name'] }}</div>
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach
</div>