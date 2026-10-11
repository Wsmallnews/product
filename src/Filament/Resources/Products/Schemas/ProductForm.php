<?php

namespace Wsmallnews\Product\Filament\Resources\Products\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;
use Wsmallnews\Product\Enums\FormLayout;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Enums\ProductStockType;
use Wsmallnews\Product\Support\Utils;
use Wsmallnews\Support\Facades\ScheduledTask;
use Wsmallnews\Support\Filament\Forms\FormComponents;

class ProductForm
{
    // ========================= 核心方法 =========================

    /**
     * 配置 schema
     *
     * 注意：wizard 布局由 HasWizard trait 处理，这里只处理 tabs 和 plain
     */
    public static function configure(Schema $schema, ?FormLayout $layout = null): Schema
    {
        $layout = $layout ?? FormLayout::tryFrom(Utils::getConfig('form_layout.layout', FormLayout::Plain->value));

        return match ($layout) {
            // wizard 由页面的 HasWizard trait 处理，这里返回原始字段
            FormLayout::Wizard => $schema->components(static::allFields()),
            FormLayout::Tabs => $schema->components([static::tabsComponent()]),
            default => $schema->components([static::plainLayout()]),
        };
    }

    // ========================= 布局方法 =========================

    /**
     * Tabs 组件
     */
    public static function tabsComponent(): Tabs
    {
        $tabs = Tabs::make('product_form')
            ->tabs([
                Tab::make('基础信息')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        Section::make()->schema(static::baseInfoFields())->columns(2),
                        Section::make('定时任务')->schema([ScheduledTask::scheduleRepeater('sn_product')])->columns(1),
                    ]),
                Tab::make('规格库存')
                    ->icon(Heroicon::OutlinedTag)
                    ->schema([
                        Section::make('库存信息')->schema(static::stockInfoFields())->columns(2),
                        Section::make('规格信息')->schema(static::specInfoFields()),
                        Section::make(__('sn-product::product.product_form.extras_section'))->schema(static::extraInfoFields())->columns(2),
                    ]),
                Tab::make('产品详情')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->schema([
                        Section::make('参数信息')->schema(static::paramsInfoFields()),
                        Section::make('产品详情')->schema(static::detailInfoFields()),
                    ]),
            ])
            ->columnSpanFull();

        // 应用配置
        if (Utils::getConfig('form_layout.tabs.scrollable', false)) {
            $tabs->scrollable();
        }

        if (Utils::getConfig('form_layout.tabs.vertical', false)) {
            $tabs->vertical();
        }

        $activeTab = Utils::getConfig('form_layout.tabs.active_tab', 1);
        if ($activeTab > 1) {
            $tabs->activeTab($activeTab);
        }

        $persistTab = Utils::getConfig('form_layout.tabs.persist_tab', 'tab');
        if ($persistTab) {
            $tabs->persistTabInQueryString($persistTab);
        }

        return $tabs;
    }

    /**
     * Plain 布局（Section 包裹）
     */
    public static function plainLayout(): Grid
    {
        return Grid::make(2)
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('基础信息')->schema(static::baseInfoFields())->columns(2),
                        Section::make('定时任务')->schema([ScheduledTask::scheduleRepeater('sn_product')])->columns(1),
                        Section::make('库存信息')->schema(static::stockInfoFields())->columns(2),
                        Section::make('规格信息')->schema(static::specInfoFields()),
                        Section::make(__('sn-product::product.product_form.extras_section'))->schema(static::extraInfoFields())->columns(2),
                        Section::make('参数信息')->schema(static::paramsInfoFields()),
                        Section::make('产品详情')->schema(static::detailInfoFields()),
                    ])
                    ->columnSpan(2),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    // ========================= Wizard 步骤 =========================

    /**
     * Wizard 步骤（供 HasWizard trait 使用）
     *
     * @return Wizard\Step[]
     */
    public static function wizardSteps(): array
    {
        return [
            Wizard\Step::make('基础信息')
                ->icon(Heroicon::OutlinedDocumentText)
                ->completedIcon(Heroicon::HandThumbUp)
                ->schema([
                    Section::make('基础信息')->schema(static::baseInfoFields())->columns(2),
                    Section::make('定时任务')->schema([ScheduledTask::scheduleRepeater('sn_product')])->columns(1),
                ]),
            Wizard\Step::make('规格库存')
                ->icon(Heroicon::OutlinedTag)
                ->completedIcon(Heroicon::HandThumbUp)
                ->schema([
                    Section::make('库存信息')->schema(static::stockInfoFields())->columns(2),
                    Section::make('规格信息')->schema(static::specInfoFields()),
                    Section::make(__('sn-product::product.product_form.extras_section'))->schema(static::extraInfoFields())->columns(2),
                ]),
            Wizard\Step::make('产品详情')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->completedIcon(Heroicon::HandThumbUp)
                ->schema([
                    Section::make('参数信息')->schema(static::paramsInfoFields()),
                    Section::make('产品详情')->schema(static::detailInfoFields()),
                ]),
        ];
    }

    // ========================= 所有字段（wizard 使用） =========================

    /**
     * 所有字段组合（wizard 布局时使用）
     */
    public static function allFields(): array
    {
        return [
            Section::make('基础信息')->schema(static::baseInfoFields())->columns(2),
            Section::make('定时任务')->schema([ScheduledTask::scheduleRepeater('sn_product')])->columns(1),
            Section::make('库存信息')->schema(static::stockInfoFields())->columns(2),
            Section::make('规格信息')->schema(static::specInfoFields()),
            Section::make(__('sn-product::product.product_form.extras_section'))->schema(static::extraInfoFields())->columns(2),
            Section::make('参数信息')->schema(static::paramsInfoFields()),
            Section::make('产品详情')->schema(static::detailInfoFields()),
        ];
    }

    // ========================= 字段组合方法 =========================

    /**
     * 基础信息字段（含产品主图与轮播图）
     */
    public static function baseInfoFields(): array
    {
        return [
            static::titleField()->columnSpan(2),
            static::subtitleField()->columnSpan(2),
            static::statusField()->columnSpan(2),
            static::orderField()->columnSpan(2),
            static::imageField()->columnSpan(2),
            static::imagesField()->columnSpan(2),
        ];
    }

    /**
     * 库存信息字段
     */
    public static function stockInfoFields(): array
    {
        return [
            static::stockTypeField(),
            static::stockUnitField(),
        ];
    }

    /**
     * 规格信息字段（四种规格类型复用 ProductSpecForm 共享编辑器）
     */
    public static function specInfoFields(): array
    {
        return [
            ProductSpecForm::specTypeField(),

            // 单规格：变体属性平铺为一级字段
            ProductSpecForm::singleSpecFieldset(),

            // 多规格 / 主多规格 / 多单位：规格项编辑器（主多规格拆为主规格 + 附加规格项）+ 规格组合
            Group::make()
                ->schema([
                    ...ProductSpecForm::specsRepeaters(),
                    ProductSpecForm::variantsRepeater(),
                ])
                ->visible(fn (Component $livewire): bool => ProductSpecForm::hasSpecs($livewire))
                ->columnSpanFull(),
        ];
    }

    /**
     * 参数信息字段
     */
    public static function paramsInfoFields(): array
    {
        return [
            static::paramsField()->columnSpanFull(),
        ];
    }

    /**
     * 附加项挂载（双层结构：先选组，再挑组内选项）：数组状态经 ExtraItemService
     * 拍平为选项粒度挂载行落库；选过的组兄弟行禁选；换组自动清空已挑选项。
     */
    public static function extraInfoFields(): array
    {
        return [
            Forms\Components\Repeater::make('extra_groups')
                ->hiddenLabel()
                ->schema([
                    // 组选择：同组已在其他行选过则禁选
                    Forms\Components\Select::make('group_id')
                        ->label(__('sn-product::product.product_form.extra_group'))
                        ->options(fn (Component $livewire) => static::extraGroupOptions($livewire))
                        ->searchable()
                        ->required()
                        ->live()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->afterStateUpdated(fn (Set $set) => $set('options', []))
                        ->columnSpan(2),

                    // 组内选项：默认选中 + 覆盖价；支持行内直建（自动归入当前组）
                    Forms\Components\Repeater::make('options')
                        ->label(__('sn-product::product.product_form.extra_options'))
                        ->schema([
                            Forms\Components\Select::make('extra_id')
                                ->label(__('sn-product::product.product_form.extra_option'))
                                ->options(function (Get $get) {
                                    $groupId = (int) $get('../../group_id');

                                    return $groupId > 0 ? static::extraOptionsOfGroup($groupId) : [];
                                })
                                ->searchable()
                                ->required()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->label(__('sn-product::product.product_form.extra_option'))
                                        ->required(),
                                    Forms\Components\TextInput::make('price')
                                        ->label(__('sn-product::product.product_form.extra_price'))
                                        ->numeric()
                                        ->prefix('￥')
                                        ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/']),
                                ])
                                ->createOptionUsing(function (array $data, Get $get) {
                                    $extraModel = Utils::getExtraModel();

                                    // 行内直建的选项自动归入当前组
                                    return $extraModel::create([
                                        'parent_id' => (int) $get('../../group_id'),
                                        'name' => $data['name'],
                                        'price' => $data['price'] ?? 0,
                                        'status' => \Wsmallnews\Product\Enums\ExtraStatus::Up,
                                    ])->getKey();
                                })
                                ->columnSpan(2),

                            Forms\Components\Toggle::make('is_default')
                                ->label(__('sn-product::product.product_form.extra_is_default')),

                            Forms\Components\TextInput::make('price_override')
                                ->label(__('sn-product::product.product_form.extra_price_override'))
                                ->helperText(__('sn-product::product.product_form.extra_price_override_helper'))
                                ->numeric()
                                ->prefix('￥')
                                ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/']),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel(__('sn-product::product.product_form.extra_add_option'))
                        ->reorderable()
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->defaultItems(0)
                ->addActionLabel(__('sn-product::product.product_form.extra_add_group'))
                ->itemLabel(fn (array $state): ?string => static::extraGroupLabel($state['group_id'] ?? null))
                ->cloneable(false)
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * 附加项库组（仅上架，scope 隔离；组选择器数据源）。
     */
    protected static function extraGroupOptions(\Livewire\Component $livewire): array
    {
        $extraModel = Utils::getExtraModel();

        return $extraModel::query()
            ->where('parent_id', 0)
            ->where('status', \Wsmallnews\Product\Enums\ExtraStatus::Up)
            ->where($livewire::getScopeable())
            ->orderBy('order_column')->orderBy('id')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * 指定组的选项（仅上架；含价格展示；组内选项选择器数据源）。
     */
    protected static function extraOptionsOfGroup(int $groupId): array
    {
        $extraModel = Utils::getExtraModel();
        $group = $extraModel::query()->find($groupId);

        if (! $group) {
            return [];
        }

        return $group->children()
            ->where('status', \Wsmallnews\Product\Enums\ExtraStatus::Up)
            ->orderBy('order_column')->orderBy('id')
            ->get()
            ->mapWithKeys(fn ($option) => [$option->id => $option->name . '（' . sn_money()->decimal($option->price) . '）'])
            ->all();
    }

    /**
     * 组行折叠标签（组名；未选时 null 显示占位）。
     */
    protected static function extraGroupLabel(?int $groupId): ?string
    {
        if (blank($groupId)) {
            return null;
        }

        $extraModel = Utils::getExtraModel();
        $group = $extraModel::query()->find($groupId);

        return $group?->name;
    }

    /**
     * 产品详情字段
     */
    public static function detailInfoFields(): array
    {
        return [
            FormComponents::contentTypeGroup(
                types: Utils::getConfig('contents.product.types'),
                defaultType: Utils::getConfig('contents.product.default_type'),
                directory: Utils::getFileDirectory('contents'),
                label: '商品详情'
            )->columnSpanFull(),
        ];
    }

    // ========================= 单个字段方法 =========================

    /**
     * 产品标题
     */
    public static function titleField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('title')
            ->label('产品标题')
            ->placeholder('请输入产品标题')
            ->required();
    }

    /**
     * 产品副标题
     */
    public static function subtitleField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('subtitle')
            ->label('产品副标题')
            ->placeholder('请输入产品副标题');
    }

    /**
     * 产品状态
     */
    public static function statusField(): Forms\Components\ToggleButtons
    {
        return FormComponents::enumsToggleButtons(ProductStatus::class);
    }

    /**
     * 排序
     */
    public static function orderField(): Forms\Components\TextInput
    {
        return FormComponents::orderColumnInput();
    }

    /**
     * 产品主图
     */
    public static function imageField(): Forms\Components\FileUpload
    {
        return FormComponents::mediaImageUpload('product_image', 'product_image')
            ->label('产品主图')
            ->required()
            ->customProperties(function (Component $livewire) {
                return [
                    ...$livewire::getScopeable(),
                    'team_id' => current_tenant()?->id,
                ];
            })
            ->uploadingMessage('产品主图上传中...');
    }

    /**
     * 产品轮播图
     */
    public static function imagesField(): Forms\Components\FileUpload
    {
        return FormComponents::mediaImageUpload('product_images', 'product_images')
            ->label('产品轮播图')
            ->customProperties(function (Component $livewire) {
                return [
                    ...$livewire::getScopeable(),
                    'team_id' => current_tenant()?->id,
                ];
            })
            ->multiple()
            ->minFiles(1)
            ->uploadingMessage('产品轮播图上传中...');
    }

    /**
     * 库存类型
     */
    public static function stockTypeField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('stock_type')
            ->label('库存类型')
            ->options(ProductStockType::class)
            ->default(ProductStockType::Infinite)
            ->live()
            ->required();
    }

    /**
     * 库存单位（多单位模式下为基准单位，变体按换算比例折算）
     */
    public static function stockUnitField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('stock_unit')
            ->label('库存单位')
            ->placeholder('件/个/箱')
            ->live(onBlur: true);
    }

    /**
     * 商品参数
     */
    public static function paramsField(): Forms\Components\KeyValue
    {
        return Forms\Components\KeyValue::make('params')
            ->label('商品参数')
            ->keyLabel('参数名')
            ->keyPlaceholder('请输入参数名')
            ->valueLabel('参数值')
            ->valuePlaceholder('请输入参数值')
            ->addActionLabel('添加参数')
            ->required()
            ->reorderable();
    }
}
