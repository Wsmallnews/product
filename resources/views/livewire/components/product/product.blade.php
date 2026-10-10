@php
    use Filament\Support\Facades\FilamentAsset;
    use Filament\Support\Facades\FilamentView;
    use Filament\Support\Icons\Heroicon;
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
                <x-sn-support::swiper :slides="$slides" class="w-full" ratio="1/1" rounded="rounded-(--sn-radius-card)" :has-thumb="true" thumb-position="left" :thumb-size="72" />
            @else
                <div class="w-full aspect-square sn-image-placeholder">
                    <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="w-12 h-12" aria-hidden="true" />
                </div>
            @endif
        </div>

        {{-- 购买盒：规格选择/价格/数量全部客户端本地计算（specSchema 契约，uniapp 接口同构）；
            wire:ignore 防止 Livewire morph 重建节点导致 Alpine 状态丢失（swiper 同模式） --}}
        <div class="w-full flex flex-col gap-y-4 min-w-0"
            @if (FilamentView::hasSpaMode())
                x-load="visible || event (ax-modal-opened)"
            @else
                x-load
            @endif
            wire:ignore
            x-load-src="{{ FilamentAsset::getAlpineComponentSrc('sn-product-components-product-buy', 'wsmallnews/product') }}"
            x-data="productBuy({
                schema: @js($specSchema),
                initialPriceParts: @js($initialPriceParts),
                currencySymbol: @js($initialPriceParts['symbol'] ?? '¥'),
            })"
        >
            <div class="flex flex-col gap-y-1">
                <div class="sn-h2-text">{{ $product->title }}</div>
                @if (filled($product->subtitle))
                    <div class="sn-descript-text text-base">{{ $product->subtitle }}</div>
                @endif
            </div>

            {{-- 价格（镜像 x-sn-support::amount 的 primary 输出结构；符号/金额串由服务端 formatParts 预制，本地仅切换） --}}
            <span class="flex items-baseline font-bold text-primary-600 dark:text-primary-400">
                <span class="text-sm mr-0.5 leading-none" x-text="priceParts?.symbol">{{ $initialPriceParts['symbol'] }}</span>
                <span class="text-2xl leading-none" x-text="priceParts?.amount">{{ $initialPriceParts['amount'] }}</span>
            </span>

            {{-- 规格选择：单规格无规格组；不可选规格值置灰禁用（本地计算，零请求） --}}
            @if ($parentSpecs->isNotEmpty())
                @foreach ($parentSpecs as $parentSpec)
                    <div class="flex flex-col gap-2">
                        <div class="sn-content-text">{{ $parentSpec->name }}</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($parentSpec->children as $child)
                                <button
                                    type="button"
                                    class="sn-badge sn-badge-lg cursor-pointer transition"
                                    :class="{
                                        'sn-badge-solid-primary': isSelected({{ $parentSpec->id }}, {{ $child->id }}),
                                        'opacity-40 cursor-not-allowed': ! isSelectable({{ $parentSpec->id }}, {{ $child->id }}) && ! isSelected({{ $parentSpec->id }}, {{ $child->id }}),
                                        'hover:opacity-80': isSelectable({{ $parentSpec->id }}, {{ $child->id }}) && ! isSelected({{ $parentSpec->id }}, {{ $child->id }}),
                                    }"
                                    :disabled="! isSelectable({{ $parentSpec->id }}, {{ $child->id }}) && ! isSelected({{ $parentSpec->id }}, {{ $child->id }})"
                                    @click="toggleSpec({{ $parentSpec->id }}, {{ $child->id }})"
                                >
                                    {{ $child->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="sn-tip-text" x-show="matchedVariant" x-cloak>
                    {{ __('sn-product::product.components.current_spec') }}：<span x-text="matchedVariant?.spec_text?.join(' / ')"></span>
                </div>
            @endif

            {{-- 附加项（加料/增值服务）：本地选择/计价；售罄置灰不可选但可见 --}}
            @if (! empty($specSchema['extras']))
                @foreach ($specSchema['extras'] as $extraGroup)
                    <div class="flex flex-col gap-2">
                        <div class="sn-content-text">
                            {{ $extraGroup['name'] }}
                            @if ($extraGroup['is_require'])<span class="text-danger-500">*</span>@endif
                            @if (filled($extraGroup['description'] ?? null))
                                <span class="sn-descript-text ml-1">{{ $extraGroup['description'] }}</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2 items-center">
                            @foreach ($extraGroup['options'] as $extraOption)
                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        class="sn-badge sn-badge-lg cursor-pointer transition"
                                        @class(['opacity-40 cursor-not-allowed' => $extraOption['sold_out']])
                                        :class="{
                                            'sn-badge-solid-primary': isExtraSelected({{ $extraGroup['id'] }}, {{ $extraOption['id'] }}),
                                            'hover:opacity-80': ! {{ $extraOption['sold_out'] ? 'true' : 'false' }} && ! isExtraSelected({{ $extraGroup['id'] }}, {{ $extraOption['id'] }}),
                                        }"
                                        @if ($extraOption['sold_out'])
                                            disabled
                                            title="{{ __('sn-product::product.components.extra_sold_out') }}"
                                        @endif
                                        @click="toggleExtraOption({{ $extraGroup['id'] }}, {{ $extraOption['id'] }})"
                                    >
                                        {{ $extraOption['name'] }}
                                        <span class="ml-1 opacity-70">{{ $extraOption['price_parts']['amount'] }}</span>
                                        @if ($extraOption['sold_out'])
                                            <span class="ml-1">{{ __('sn-product::product.components.extra_sold_out') }}</span>
                                        @endif
                                    </button>
                                    @if ($extraGroup['is_num'])
                                        <div class="w-28" x-show="isExtraSelected({{ $extraGroup['id'] }}, {{ $extraOption['id'] }})" x-cloak>
                                            <x-sn-support::input.step-number
                                                :min="$extraOption['limit_min_num']"
                                                :max="$extraOption['limit_max_num'] > 0 ? $extraOption['limit_max_num'] : 99"
                                                x-model="extraNums[{{ $extraOption['id'] }}]"
                                            />
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- 附加项合计（选中后实时显示） --}}
                <div class="sn-tip-text flex items-center gap-2" x-show="extrasTotalMinor > 0" x-cloak>
                    <span>{{ __('sn-product::product.components.extras_total') }}：</span>
                    <span class="font-semibold" x-text="extrasTotalText"></span>
                </div>
            @endif

            {{-- 数量（本地状态，随 buy 请求一并提交） --}}
            <div class="flex items-center gap-4">
                <div class="sn-content-text shrink-0">{{ __('sn-product::product.components.product_num') }}</div>
                <div class="w-40 shrink-0">
                    <x-sn-support::input.step-number :min="1" x-model="productNum" />
                </div>
            </div>

            {{-- 服务端校验后派发 sn-product-buy（防已下架/无货变体直达确认页）；本地选中态经参数传回 --}}
            <x-filament::button @click="$wire.buy(selectedVariantId, productNum, extrasPayload)" size="xl" class="w-48">
                {{ __('sn-product::product.components.buy_now') }}
            </x-filament::button>
        </div>
    </div>

    {{-- 参数与图文详情（同一卡片内锚点切换，滚动联动 tab 高亮；资产组件注册，勿用内联脚本） --}}
    @if (filled($product->params) || filled($product->content?->content))
        <div @class([
            'w-full sn-container sn-padded grid grid-cols-1',
        ])
            @if (FilamentView::hasSpaMode())
                x-load="visible || event (ax-modal-opened)"
            @else
                x-load
            @endif
            wire:ignore
            x-load-src="{{ FilamentAsset::getAlpineComponentSrc('sn-product-components-product-tabs', 'wsmallnews/product') }}"
            x-data="productTabs()"
        >
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
