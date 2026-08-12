<?php

namespace Wsmallnews\Product\Filament\Resources\Products\Schemas;

use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;
use Wsmallnews\Product\Enums\FormLayout;
use Wsmallnews\Product\Enums\ProductSkuType;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Product;
use Wsmallnews\Product\Support\Utils;
use Wsmallnews\Support\Filament\Forms\FormComponents;
use Wsmallnews\Support\Facades\ScheduledTask;

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
                        Section::make('图片信息')->schema(static::imageInfoFields())->columns(2),
                    ]),
                Tab::make('规格库存')
                    ->icon(Heroicon::OutlinedTag)
                    ->schema([
                        Section::make('库存信息')->schema(static::stockInfoFields())->columns(2),
                        Section::make('规格信息')->schema(static::skuInfoFields())->columns(2),
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
                        Section::make('图片信息')->schema(static::imageInfoFields())->columns(2),
                        Section::make('库存信息')->schema(static::stockInfoFields())->columns(2),
                        Section::make('规格信息')->schema(static::skuInfoFields())->columns(2),
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
                    Section::make('图片信息')->schema(static::imageInfoFields())->columns(2),
                ]),
            Wizard\Step::make('规格库存')
                ->icon(Heroicon::OutlinedTag)
                ->completedIcon(Heroicon::HandThumbUp)
                ->schema([
                    Section::make('库存信息')->schema(static::stockInfoFields())->columns(2),
                    Section::make('规格信息')->schema(static::skuInfoFields())->columns(2),
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
            Section::make('图片信息')->schema(static::imageInfoFields())->columns(2),
            Section::make('库存信息')->schema(static::stockInfoFields())->columns(2),
            Section::make('规格信息')->schema(static::skuInfoFields())->columns(2),
            Section::make('参数信息')->schema(static::paramsInfoFields()),
            Section::make('产品详情')->schema(static::detailInfoFields()),
        ];
    }

    // ========================= 字段组合方法 =========================

    /**
     * 基础信息字段
     */
    public static function baseInfoFields(): array
    {
        return [
            static::titleField()->columnSpan(2),
            static::subtitleField()->columnSpan(2),
            static::statusField()->columnSpan(2),
            ScheduledTask::scheduleRepeater('sn_product')->columnSpan(2),
            static::orderField()->columnSpan(2),
        ];
    }

    /**
     * 图片信息字段
     */
    public static function imageInfoFields(): array
    {
        return [
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
            static::showSalesField(),
        ];
    }

    /**
     * 规格信息字段
     */
    public static function skuInfoFields(): array
    {
        return [
            static::skuTypeField()->columnSpanFull(),
            static::skuSimpleField()
                ->visible(fn (Get $get): bool => $get('sku_type') == ProductSkuType::Single->value),
            // static::skuMultipleField()
            //     ->visible(fn (Get $get): bool => $get('sku_type') == ProductSkuType::Multiple->value)
            //     ->columnSpanFull(),
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
     * 产品详情字段
     */
    public static function detailInfoFields(): array
    {
        return [
            static::richContentField()->columnSpanFull(),
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
        return Forms\Components\ToggleButtons::make('status')
            ->default(ProductStatus::Up)
            ->inline()
            ->options(ProductStatus::class);
    }

    /**
     * 排序
     */
    public static function orderField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('order_column')
            ->label('排序')
            ->integer()
            ->placeholder('正序排列')
            ->rules(['integer', 'min:0']);
    }

    /**
     * 产品主图
     */
    public static function imageField(): Forms\Components\FileUpload
    {
        return FormComponents::mediaImageUpload('product_image', 'product_image')
            ->label('产品主图')->required()
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
            ->options([
                'none' => '无库存',
                'stock' => '有库存',
            ])
            ->default('none')
            ->required();
    }

    /**
     * 库存单位
     */
    public static function stockUnitField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('stock_unit')
            ->label('库存单位')
            ->placeholder('件/个/箱');
    }

    /**
     * 显示销量
     */
    public static function showSalesField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('show_sales')
            ->label('显示销量')
            ->integer()
            ->default(0)
            ->placeholder('0');
    }

    /**
     * 规格类型
     */
    public static function skuTypeField(): Forms\Components\ToggleButtons
    {
        return Forms\Components\ToggleButtons::make('sku_type')
            ->default(ProductSkuType::Single)
            ->inline()
            ->options(ProductSkuType::class);
    }

    /**
     * 单规格
     */
    public static function skuSimpleField(): Forms\Components\KeyValue
    {
        return Forms\Components\KeyValue::make('sku_simple')
            ->label('规格信息')
            ->keyLabel('属性名')
            ->keyPlaceholder('如：颜色')
            ->valueLabel('属性值')
            ->valuePlaceholder('如：红色')
            ->addActionLabel('添加属性')
            ->reorderable();
    }

    /**
     * 多规格
     */
    // public static function skuMultipleField(): Schemas\Components\Arrange
    // {
    //     return \Wsmallnews\Support\Filament\Forms\Fields\Arrange::make('sku_multiple')
    //         ->label('规格信息')
    //         ->schema([
    //             Forms\Components\TextInput::make('name')
    //                 ->label('规格名')
    //                 ->placeholder('如：颜色')
    //                 ->required(),
    //             Forms\Components\Repeater::make('values')
    //                 ->label('规格值')
    //                 ->schema([
    //                     Forms\Components\TextInput::make('name')
    //                         ->label('值名称')
    //                         ->placeholder('如：红色')
    //                         ->required(),
    //                 ])
    //                 ->arrangePlaceholder('请填写规格名')
    //                 ->arrangeChildPlaceholder('请填写子规格名')
    //                 ->addActionLabel('添加规格值')
    //                 ->reorderable(),
    //         ])
    //         ->arrangePlaceholder('请填写规格名')
    //         ->arrangeChildPlaceholder('请填写子规格名')
    //         ->addActionLabel('添加规格')
    //         ->addChildActionLabel('添加子规格')
    //         ->required()
    //         ->columnSpanFull();
    // }

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

    /**
     * 商品详情（富文本）
     */
    public static function richContentField(): Forms\Components\RichEditor
    {
        return FormComponents::richEditor('content')
            ->label('商品详情')
            // ->fileAttachmentsDirectory(Product::getImageDirectory())
            ;
    }

    // /**
    //  * 商品详情（Markdown）
    //  */
    // public static function markdownContentField(): Forms\Components\MarkdownEditor
    // {
    //     return FormComponents::markdownEditor('content')
    //         ->label('商品详情')
    //         ->fileAttachmentsDirectory(Product::getImageDirectory());
    // }

    // ========================= 价格字段 =========================

    /**
     * 原价
     */
    public static function originalPriceField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('original_price')
            ->label('原价')
            ->formatStateUsing(sn_currency()->filamentFormState)
            ->suffix(sn_currency()->filamentFormSymbol)
            ->numeric()
            ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/'])
            ->required();
    }

    /**
     * 成本价
     */
    public static function costPriceField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('cost_price')
            ->label('成本价')
            ->formatStateUsing(sn_currency()->filamentFormState)
            ->suffix(sn_currency()->filamentFormSymbol)
            ->helperText('用户无法看到成本价')
            ->numeric()
            ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/'])
            ->required();
    }

    /**
     * 售卖价
     */
    public static function priceField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('price')
            ->label('售卖价')
            ->formatStateUsing(sn_currency()->filamentFormState)
            ->suffix(sn_currency()->filamentFormSymbol)
            ->numeric()
            ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/'])
            ->required();
    }

    /**
     * 库存
     */
    public static function stockField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('stock')
            ->label('库存')
            ->integer()
            ->suffix(fn (Get $get): ?string => $get('../stock_unit'));
    }

    /**
     * 重量
     */
    public static function weightField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('weight')
            ->label('重量');
    }

    /**
     * 货号
     */
    public static function productSnField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('product_sn')
            ->label('货号');
    }
}
