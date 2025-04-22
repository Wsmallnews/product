<div class="w-full overflow-hidden">
    @foreach ($this->showSkus as $sku)
        <div class="flex items-center">
            <div class="w-16 mr-4 text-sm text-gray-600 shrink-0 grow-0">{{ $sku['name'] }}</div>
            <div class="flex flex-wrap grow">
                @foreach ($sku['children'] as $child)
                    <button @class([
                            'flex grow-0 shrink-0 basis-auto items-center mr-3 mb-3 rounded-md disabled:opacity-50 ring-1',
                            'bg-primary-600 text-white ring-primary-600' => $this->checkIsChoosed($sku['id'], $child['id']),
                            'ring-gray-300 hover:ring-primary-600' => !$this->checkIsChoosed($sku['id'], $child['id']),
                        ])
                        :disabled="{{ $child['disabled'] }}"
                        wire:click="choose({{$sku['id']}}, {{$child['id']}})"
                    >
                        @if ($child['image'])
                            <div class="w-7 h-7 overflow-hidden rounded-md">
                                <img class="w-full h-full object-contain" :src="{{$child['image']}}" :alt="{{ $child['name'] }}"/>
                            </div>
                        @endif
                        <div class="flex-1 truncate px-3 py-1">{{ $child['name'] }}</div>
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach
</div>