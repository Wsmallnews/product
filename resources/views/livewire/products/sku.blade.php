<div class="flex flex-col w-full gap-y-4">
    @foreach ($this->showSkus as $sku)
        <div class="flex items-start">
            <div class="w-16 mr-4 text-sm text-gray-600 shrink-0 grow-0 py-2">{{ $sku['name'] }}</div>
            <div class="flex flex-wrap gap-4">
                @foreach ($sku['children'] as $child)
                    <button @class([
                            'flex items-center max-w-full grow-0 shrink-0 basis-auto rounded-md disabled:opacity-50 ring-1',
                            'bg-primary-600 text-white ring-primary-600' => $this->checkIsChoosed($sku['id'], $child['id']),
                            'ring-gray-300 hover:ring-primary-600' => !$this->checkIsChoosed($sku['id'], $child['id']),
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