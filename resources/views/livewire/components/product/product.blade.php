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
                initialPriceMinor: @js($initialPriceMinor),
                mainImageUrl: @js($mainImageUrl),
                currencySymbol: @js($initialPriceParts['symbol'] ?? '¥'),
                langRemainingStock: @js(__('sn-product::product.components.remaining_stock', ['num' => ':num'])),
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

            {{-- 规格选择：单规格无规格组；组内任一规格值有图 → 列表形态（紧凑流式卡片），无图 → 胶囊；
                长名超整行才截断、选中展开；不可选置灰禁用（本地计算，零请求） --}}
            @if ($parentSpecs->isNotEmpty())
                @foreach ($parentSpecs as $parentSpec)
                    @php
                        $groupHasImage = $parentSpec->children->contains(fn ($child) => filled($child->image));
                    @endphp
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-baseline gap-1.5">
                                <div class="sn-content-text">{{ $parentSpec->name }}</div>
                                <span class="text-xs text-gray-400">{{ $parentSpec->children->count() }}</span>
                            </div>

                            @if ($groupHasImage)
                                {{-- 带图规格组：列表 / 大图形态切换（淘宝同款入口；无图组恒胶囊不参与切换） --}}
                                <div class="flex items-center gap-0.5 rounded-full bg-gray-100 p-0.5 dark:bg-gray-800">
                                    <button
                                        type="button"
                                        class="grid size-6 cursor-pointer place-items-center rounded-full text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-300"
                                        :class="specMode({{ $parentSpec->id }}) === 'list' ? 'bg-white text-primary-600 shadow-sm dark:text-primary-400' : ''"
                                        title="{{ __('sn-product::product.components.spec_mode_list') }}"
                                        @click="setSpecMode({{ $parentSpec->id }}, 'list')"
                                    >
                                        <x-filament::icon :icon="Heroicon::OutlinedBars3" class="size-3.5" />
                                    </button>
                                    <button
                                        type="button"
                                        class="grid size-6 cursor-pointer place-items-center rounded-full text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-300"
                                        :class="specMode({{ $parentSpec->id }}) === 'grid' ? 'bg-white text-primary-600 shadow-sm dark:text-primary-400' : ''"
                                        title="{{ __('sn-product::product.components.spec_mode_grid') }}"
                                        @click="setSpecMode({{ $parentSpec->id }}, 'grid')"
                                    >
                                        <x-filament::icon :icon="Heroicon::OutlinedSquares2x2" class="size-3.5" />
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if ($groupHasImage)
                            {{-- 列表形态：宽度随内容流式换行；小图 + 名称 + 该值最低变体价 --}}
                            <div class="flex flex-wrap gap-2" x-show="specMode({{ $parentSpec->id }}) === 'list'">
                                @foreach ($parentSpec->children as $child)
                                    <button
                                        type="button"
                                        class="inline-flex max-w-full items-center gap-2 rounded-(--sn-radius-control) border border-gray-200 py-1.5 pl-1.5 pr-3 text-left transition dark:border-gray-700"
                                        :class="{
                                            'border-primary-500 bg-primary-500/10 dark:border-primary-400': isSelected({{ $parentSpec->id }}, {{ $child->id }}),
                                            'hover:border-gray-300 dark:hover:border-gray-600': ! isSelected({{ $parentSpec->id }}, {{ $child->id }}) && isSelectable({{ $parentSpec->id }}, {{ $child->id }}),
                                            'opacity-40 cursor-not-allowed': ! isSelectable({{ $parentSpec->id }}, {{ $child->id }}) && ! isSelected({{ $parentSpec->id }}, {{ $child->id }}),
                                        }"
                                        :disabled="! isSelectable({{ $parentSpec->id }}, {{ $child->id }}) && ! isSelected({{ $parentSpec->id }}, {{ $child->id }})"
                                        title="{{ $child->name }}"
                                        @click="toggleSpec({{ $parentSpec->id }}, {{ $child->id }})"
                                    >
                                        @if (filled($child->image))
                                            <img src="{{ $child->image }}" alt="{{ $child->name }}" class="size-10 shrink-0 rounded-(--sn-radius-control) object-cover">
                                        @else
                                            <span class="grid size-10 shrink-0 place-items-center rounded-(--sn-radius-control) sn-media-bg">
                                                <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="size-4 text-gray-400" aria-hidden="true" />
                                            </span>
                                        @endif
                                        <span class="flex min-w-0 flex-col items-start">
                                            <span class="max-w-full text-sm font-medium leading-5"
                                                :class="isSelected({{ $parentSpec->id }}, {{ $child->id }}) ? 'whitespace-normal text-primary-600 dark:text-primary-400' : 'truncate'"
                                            >{{ $child->name }}</span>
                                            <span class="text-[11px] leading-4 text-primary-600 dark:text-primary-400"
                                                x-text="childMinPriceParts({{ $child->id }})"></span>
                                        </span>
                                    </button>
                                @endforeach
                            </div>

                            {{-- 大图形态：auto-fill 自适应网格（卡宽钳 ~120-160px，列数随容器），图上角标选中 --}}
                            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(120px,100%),1fr))] gap-2" x-show="specMode({{ $parentSpec->id }}) === 'grid'" x-cloak>
                                @foreach ($parentSpec->children as $child)
                                    <button
                                        type="button"
                                        class="rounded-(--sn-radius-control) border border-gray-200 p-1 text-left transition dark:border-gray-700"
                                        :class="{
                                            'border-primary-500 bg-primary-500/10 dark:border-primary-400': isSelected({{ $parentSpec->id }}, {{ $child->id }}),
                                            'hover:border-gray-300 dark:hover:border-gray-600': ! isSelected({{ $parentSpec->id }}, {{ $child->id }}) && isSelectable({{ $parentSpec->id }}, {{ $child->id }}),
                                            'opacity-40 cursor-not-allowed': ! isSelectable({{ $parentSpec->id }}, {{ $child->id }}) && ! isSelected({{ $parentSpec->id }}, {{ $child->id }}),
                                        }"
                                        :disabled="! isSelectable({{ $parentSpec->id }}, {{ $child->id }}) && ! isSelected({{ $parentSpec->id }}, {{ $child->id }})"
                                        title="{{ $child->name }}"
                                        @click="toggleSpec({{ $parentSpec->id }}, {{ $child->id }})"
                                    >
                                        <span class="relative block">
                                            @if (filled($child->image))
                                                <img src="{{ $child->image }}" alt="{{ $child->name }}" class="aspect-square w-full rounded-(--sn-radius-control) object-cover">
                                            @else
                                                <span class="grid aspect-square w-full place-items-center rounded-(--sn-radius-control) sn-media-bg">
                                                    <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="size-6 text-gray-400" aria-hidden="true" />
                                                </span>
                                            @endif
                                            <span
                                                x-show="isSelected({{ $parentSpec->id }}, {{ $child->id }})"
                                                x-cloak
                                                class="absolute right-1 top-1 grid size-5 place-items-center rounded-full bg-primary-500 text-white shadow"
                                            >
                                                <x-filament::icon :icon="Heroicon::Check" class="size-3" />
                                            </span>
                                        </span>
                                        <span class="mt-1 block truncate text-center text-xs font-medium">{{ $child->name }}</span>
                                        <span class="block text-center text-[11px] leading-4 text-primary-600 dark:text-primary-400"
                                            x-text="childMinPriceParts({{ $child->id }})"></span>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            {{-- 胶囊形态（sn-badge 体系） --}}
                            <div class="flex flex-wrap gap-2">
                                @foreach ($parentSpec->children as $child)
                                    <button
                                        type="button"
                                        class="sn-badge sn-badge-lg sn-badge-gray max-w-full cursor-pointer text-left transition"
                                        :class="{
                                            'sn-badge-solid-primary': isSelected({{ $parentSpec->id }}, {{ $child->id }}),
                                            'opacity-40 cursor-not-allowed': ! isSelectable({{ $parentSpec->id }}, {{ $child->id }}) && ! isSelected({{ $parentSpec->id }}, {{ $child->id }}),
                                            'hover:opacity-80': isSelectable({{ $parentSpec->id }}, {{ $child->id }}) && ! isSelected({{ $parentSpec->id }}, {{ $child->id }}),
                                        }"
                                        :disabled="! isSelectable({{ $parentSpec->id }}, {{ $child->id }}) && ! isSelected({{ $parentSpec->id }}, {{ $child->id }})"
                                        title="{{ $child->name }}"
                                        @click="toggleSpec({{ $parentSpec->id }}, {{ $child->id }})"
                                    >
                                        <span class="min-w-0 max-w-full block"
                                            :class="isSelected({{ $parentSpec->id }}, {{ $child->id }}) ? 'whitespace-normal' : 'truncate'"
                                        >{{ $child->name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif

            {{-- 附加项（加料/增值服务）：无数量组 = 规格胶囊同款；有数量组 = 行式卡片（容器 ≥400px 两列）+ 裸步进器；
                售罄置灰不可选但可见 --}}
            @if (! empty($specSchema['extras']))
                @foreach ($specSchema['extras'] as $extraGroup)
                    <div class="flex flex-col gap-2">
                        <div class="flex items-baseline gap-1.5">
                            <div class="sn-content-text">
                                {{ $extraGroup['name'] }}
                                @if ($extraGroup['is_require'])<span class="text-danger-500">*</span>@endif
                            </div>
                            <span class="text-xs text-gray-400">{{ $extraGroup['is_num'] ? __('sn-product::product.components.extra_multi') : __('sn-product::product.components.extra_single') }}</span>
                            @if (filled($extraGroup['description'] ?? null))
                                <span class="sn-descript-text ml-1">{{ $extraGroup['description'] }}</span>
                            @endif
                        </div>

                        @if ($extraGroup['is_num'])
                            {{-- 有数量组：行式卡片，容器查询两列 --}}
                            <div class="w-full @container">
                                <div class="grid grid-cols-1 gap-2 @[400px]:grid-cols-2">
                                    @foreach ($extraGroup['options'] as $extraOption)
                                        <div class="flex items-center justify-between gap-2 rounded-(--sn-radius-control) border border-gray-200 px-3 py-2.5 transition dark:border-gray-700"
                                            :class="{
                                                'opacity-50 cursor-not-allowed': {{ $extraOption['sold_out'] ? 'true' : 'false' }},
                                                'border-primary-400/70 bg-primary-500/10 dark:border-primary-400/50': isExtraSelected({{ $extraGroup['id'] }}, {{ $extraOption['id'] }}),
                                            }"
                                        >
                                            <div class="flex min-w-0 flex-1 items-baseline gap-1.5">
                                                <span class="truncate text-sm font-medium" title="{{ $extraOption['name'] }}">{{ $extraOption['name'] }}</span>
                                                <span class="shrink-0 text-xs text-primary-600 dark:text-primary-400">{{ $extraOption['price_parts']['symbol'] }}{{ $extraOption['price_parts']['amount'] }}</span>
                                                @if ($extraOption['sold_out'])
                                                    <span class="sn-badge sn-badge-xs sn-badge-solid-gray shrink-0">{{ __('sn-product::product.components.extra_sold_out') }}</span>
                                                @endif
                                            </div>
                                            @if (! $extraOption['sold_out'])
                                                {{-- 未选：圆形加号；选中：裸步进器（min-behavior=dispatch 减到下限 = 取消选择） --}}
                                                <button
                                                    type="button"
                                                    x-show="! isExtraSelected({{ $extraGroup['id'] }}, {{ $extraOption['id'] }})"
                                                    class="grid size-6 shrink-0 cursor-pointer place-items-center rounded-full border border-primary-500/50 text-primary-600 transition hover:bg-primary-500/10 dark:border-primary-400/50 dark:text-primary-400"
                                                    @click="toggleExtraOption({{ $extraGroup['id'] }}, {{ $extraOption['id'] }})"
                                                >
                                                    <x-filament::icon :icon="Heroicon::Plus" class="size-4" />
                                                </button>
                                                <div x-show="isExtraSelected({{ $extraGroup['id'] }}, {{ $extraOption['id'] }})" x-cloak>
                                                    <x-sn-support::input.step-number
                                                        variant="bare"
                                                        :editable="false"
                                                        min-behavior="dispatch"
                                                        :min="$extraOption['limit_min_num']"
                                                        :max="$extraOption['limit_max_num'] > 0 ? $extraOption['limit_max_num'] : 99"
                                                        button-class="grid size-6 shrink-0 cursor-pointer place-items-center rounded-full border border-primary-500/50 text-primary-600 hover:bg-primary-500/10 disabled:opacity-40 disabled:cursor-not-allowed dark:border-primary-400/50 dark:text-primary-400"
                                                        x-model="extraNums[{{ $extraOption['id'] }}]"
                                                        @sn-stepper-min="toggleExtraOption({{ $extraGroup['id'] }}, {{ $extraOption['id'] }})"
                                                    />
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- 无数量组：与规格胶囊同款（选中实底反白） --}}
                            <div class="flex flex-wrap gap-2">
                                @foreach ($extraGroup['options'] as $extraOption)
                                    <button
                                        type="button"
                                        class="sn-badge sn-badge-lg sn-badge-gray max-w-full cursor-pointer text-left transition"
                                        :class="{
                                            'sn-badge-solid-primary': isExtraSelected({{ $extraGroup['id'] }}, {{ $extraOption['id'] }}),
                                            'opacity-40 cursor-not-allowed': {{ $extraOption['sold_out'] ? 'true' : 'false' }},
                                            'hover:opacity-80': ! {{ $extraOption['sold_out'] ? 'true' : 'false' }} && ! isExtraSelected({{ $extraGroup['id'] }}, {{ $extraOption['id'] }}),
                                        }"
                                        @if ($extraOption['sold_out'])
                                            disabled
                                            title="{{ __('sn-product::product.components.extra_sold_out') }}"
                                        @else
                                            title="{{ $extraOption['name'] }}"
                                        @endif
                                        @click="toggleExtraOption({{ $extraGroup['id'] }}, {{ $extraOption['id'] }})"
                                    >
                                        <span class="min-w-0 max-w-full block"
                                            :class="isExtraSelected({{ $extraGroup['id'] }}, {{ $extraOption['id'] }}) ? 'whitespace-normal' : 'truncate'"
                                        >{{ $extraOption['name'] }}</span>
                                        <span class="shrink-0 text-xs opacity-70">{{ $extraOption['price_parts']['amount'] }}</span>
                                        @if ($extraOption['sold_out'])
                                            <span class="sn-badge sn-badge-xs sn-badge-solid-gray shrink-0">{{ __('sn-product::product.components.extra_sold_out') }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif

            {{-- 数量（本地状态，随 buy 请求一并提交） --}}
            <div class="flex items-center gap-4">
                <div class="sn-content-text shrink-0">{{ __('sn-product::product.components.product_num') }}</div>
                <div class="w-40 shrink-0">
                    <x-sn-support::input.step-number :min="1" x-model="productNum" />
                </div>
            </div>

            {{-- 底部结算条：组合图自动推导（变体覆盖图 > 带图规格组选中值 > 主图）+ 已选回显 + 合计 + 低库存提醒；
                服务端校验后派发 sn-product-buy（防已下架/无货变体直达确认页） --}}
            <div class="sticky bottom-0 z-10 flex items-center gap-3 rounded-(--sn-radius-card) border border-gray-200 bg-white/95 px-3 py-2.5 shadow-(--sn-shadow-card-hover) backdrop-blur dark:border-gray-700 dark:bg-gray-800/95">
                <template x-if="variantImage">
                    <img :src="variantImage" alt="{{ $product->title }}" class="size-12 shrink-0 rounded-(--sn-radius-control) border border-gray-200 object-cover dark:border-gray-700">
                </template>
                <span x-show="! variantImage" x-cloak class="grid size-12 shrink-0 place-items-center rounded-(--sn-radius-control) border border-gray-200 sn-media-bg dark:border-gray-700">
                    <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="size-6 text-gray-400" aria-hidden="true" />
                </span>

                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm">
                        <span class="text-gray-400">{{ __('sn-product::product.components.selected') }}：</span><span class="font-medium" x-text="selectedSpecText || '{{ __('sn-product::product.components.please_select') }}'"></span>
                    </div>
                    <div class="mt-0.5 flex items-baseline gap-1.5">
                        <span class="text-lg font-bold text-primary-600 dark:text-primary-400" x-text="totalText"></span>
                        <span x-show="stockHint !== null" x-cloak class="shrink-0 text-[11px] text-warning-600 dark:text-warning-400" x-text="remainingStockText"></span>
                        <span x-show="extrasText" x-cloak class="truncate text-[11px] text-gray-400" x-text="extrasText"></span>
                    </div>
                </div>

                <x-filament::button @click="$wire.buy(selectedVariantId, productNum, extrasPayload)" size="xl" class="shrink-0">
                    {{ __('sn-product::product.components.buy_now') }}
                </x-filament::button>
            </div>
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
