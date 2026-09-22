@php
    use Filament\Support\Icons\Heroicon;

    $displayPrice = $choosedVariant ? $choosedVariant->price : $product->price;
@endphp

{{-- 自含容器：根元素不带响应类，@container 直接加根上（详情两栏等断点按容器宽度） --}}
<div class="w-full flex flex-col sn-gap @container">
    <x-sn-support::loading.overlay />

    {{-- 基本信息：轮播图 + 标题/价格/规格/数量/购买（内容贴卡型：contained 时加卡片皮） --}}
    <div @class([
        'w-full grid grid-cols-1 @2xl:grid-cols-2 gap-4 @2xl:gap-6',
        'sn-container sn-padded' => $contained,
    ])>
        <div class="w-full min-w-0">
            @if ($slides)
                <x-sn-support::swiper :slides="$slides" class="w-full" ratio="1/1" :has-thumb="true" thumb-position="left" :thumb-size="72" />
            @else
                <div class="w-full aspect-square sn-image-placeholder">
                    <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="w-12 h-12" aria-hidden="true" />
                </div>
            @endif
        </div>

        <div class="w-full flex flex-col gap-y-4 min-w-0">
            <div class="flex flex-col gap-y-1">
                <div class="sn-h2-text">{{ $product->title }}</div>
                @if (filled($product->subtitle))
                    <div class="sn-descript-text text-base">{{ $product->subtitle }}</div>
                @endif
            </div>

            <x-sn-support::amount :amount="$displayPrice" />

            {{-- 规格选择：单规格无规格组；不可选规格值置灰禁用 --}}
            @if ($parentSpecs->isNotEmpty())
                @foreach ($parentSpecs as $parentSpec)
                    <div class="flex flex-col gap-2">
                        <div class="sn-content-text">{{ $parentSpec->name }}</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($parentSpec->children as $child)
                                @php
                                    $selectable = $specSelectable[$parentSpec->id][$child->id] ?? false;
                                    $choosed = ($selectedSpecs[$parentSpec->id] ?? null) === $child->id;
                                @endphp
                                <button
                                    type="button"
                                    wire:click="chooseSpec({{ $parentSpec->id }}, {{ $child->id }})"
                                    @disabled(! $selectable && ! $choosed)
                                    @class([
                                        'sn-badge sn-badge-lg cursor-pointer transition',
                                        'sn-badge-solid-primary' => $choosed,
                                        'opacity-40 cursor-not-allowed' => ! $selectable && ! $choosed,
                                        'hover:opacity-80' => $selectable && ! $choosed,
                                    ])
                                >
                                    {{ $child->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if ($choosedVariant && filled($choosedVariant->product_spec_text))
                    <div class="sn-tip-text">
                        {{ __('sn-product::product.components.current_spec') }}：{{ implode(' / ', $choosedVariant->product_spec_text) }}
                    </div>
                @endif
            @endif

            {{-- 数量 --}}
            <div class="flex items-center gap-4">
                <div class="sn-content-text shrink-0">{{ __('sn-product::product.components.product_num') }}</div>
                <div class="w-40 shrink-0">
                    <x-sn-support::input.step-number :min="1" wire:model="productNum" />
                </div>
            </div>

            <x-filament::button wire:click="buy" size="xl" class="w-48">
                {{ __('sn-product::product.components.buy_now') }}
            </x-filament::button>
        </div>
    </div>

    {{-- 参数与图文详情（同一卡片内锚点切换，滚动联动 tab 高亮） --}}
    @if (filled($product->params) || filled($product->content?->content))
        <div @class([
            'w-full sn-container sn-padded grid grid-cols-1',
        ]) x-data="snProductTabs()">
            <x-sn-support::tabs label="Content tabs" :contained="true" class="sticky top-0">
                @if (filled($product->params))
                    <x-sn-support::tabs.item tag="a" href="#product-params" alpine-active="currentTab == 'product-params'">
                        {{ __('sn-product::product.components.product_params') }}
                    </x-sn-support::tabs.item>
                @endif

                @if (filled($product->content?->content))
                    <x-sn-support::tabs.item tag="a" href="#product-content" alpine-active="currentTab == 'product-content'">
                        {{ __('sn-product::product.components.product_content') }}
                    </x-sn-support::tabs.item>
                @endif
            </x-sn-support::tabs>

            <div class="w-full @5xl:w-2/3">
                @if (filled($product->params))
                    <div class="w-full">
                        <div id="product-params" class="tab-content sn-h3-text sn-my scroll-mt-16">{{ __('sn-product::product.components.product_params') }}</div>
                        <div class="w-full flex flex-row flex-wrap overflow-hidden sn-rounded border-t border-l border-gray-200 dark:border-gray-700">
                            @foreach ($product->params as $key => $param)
                                <div class="w-full @2xl:w-1/2 min-h-14 flex flex-row justify-start items-center border-r border-b border-gray-200 dark:border-gray-700">
                                    <div class="w-2/5 h-full flex items-center bg-gray-100 dark:bg-gray-800 sn-px sn-content-text">{{ $key }}</div>
                                    <div class="w-3/5 h-full flex items-center sn-px sn-content-text">{{ $param }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (filled($product->content?->content))
                    <div class="w-full">
                        <div id="product-content" class="tab-content sn-h3-text sn-my scroll-mt-16">{{ __('sn-product::product.components.product_content') }}</div>
                        <div class="w-full">
                            <x-sn-support::content
                                :content-type="$product->content->content_type"
                                :content="$product->content->content"
                            />
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@assets
    <script>
        document.addEventListener('alpine:init', () => {
            // 详情锚点 tab：滚动到对应区块（.tab-content）时高亮 tab（tabs.item 的 alpine-active 消费 currentTab）
            window.snProductTabs = () => ({
                currentTab: null,
                init() {
                    window.addEventListener('scroll', () => {
                        this.$root.querySelectorAll('.tab-content').forEach((tabContent) => {
                            const rect = tabContent.getBoundingClientRect();

                            if (rect.top <= 100 && rect.bottom >= 100) {
                                this.currentTab = tabContent.getAttribute('id');
                            }
                        });
                    });
                },
            });
        });
    </script>
@endassets
