<?php

namespace Wsmallnews\Product\Repositories;

use Filament\Forms\Get;
use Filament\Forms\Components;
use Illuminate\Database\Eloquent\Builder;
use Wsmallnews\Product\Enums;
use Wsmallnews\Product\Product;
use Wsmallnews\Support\Forms\Fields\Arrange;

class Fields
{

    /**
     * 产品标题
     *
     * @return Components\TextInput
     */
    public static function title()
    {
        return Components\TextInput::make('title')->label('产品标题')->placeholder('请输入产品标题')->required();
    }


    /**
     * 产品副标题
     *
     * @return Components\TextInput
     */
    public static function subtitle()
    {
        return Components\TextInput::make('subtitle')->label('产品副标题')->placeholder('请输入产品副标题');
    }


    public static function category()
    {
        // @sn todo 分类完善
    }


    /**
     * 产品主图
     *
     * @return Components\FileUpload
     */
    public static function image()
    {
        return Components\FileUpload::make('image')->label('产品主图')
            ->image()
            ->directory(Product::getImageDirectory())
            ->required()
            ->openable()
            ->downloadable()
            ->uploadingMessage('产品主图上传中...')
            ->imagePreviewHeight('100');
    }


    /**
     * 产品轮播图
     *
     * @return Components\FileUpload
     */
    public static function images()
    {
        return Components\FileUpload::make('images')->label('产品轮播图')
            ->image()
            ->directory(Product::getImageDirectory())
            ->required()
            ->multiple()
            ->openable()
            ->downloadable()
            ->reorderable()
            ->appendFiles()
            ->minFiles(1)
            ->maxFiles(20)
            ->uploadingMessage('轮播图片上传中...')
            ->imagePreviewHeight('100');
    }


    /**
     * 产品库存类型
     *
     * @return Components\TextInput
     */
    public static function stockType()
    {
        return Components\Radio::make('stock_type')
        ->label('库存类型')
        ->options(Enums\ProductStockType::class)
            ->default(Enums\ProductStockType::Stock->value)
            ->live()
            ->required();
    }


    /**
     * 产品基础库存单位
     *
     * @return Components\TextInput
     */
    public static function stockUnit()
    {
        return Components\Select::make('stock_unit')
            // ->relationship(name: 'stockUnit', titleAttribute: 'name', modifyQueryUsing: fn(Builder $query) => $query->scopeable())      // @sn todo 这里要考虑 scope 的问题
            ->relationship(name: 'stockUnit', titleAttribute: 'name', modifyQueryUsing: function (Builder $query, Components\Select $component) {
                $scopeInfo = $component->getLivewire()->getScopeInfo();
                return $query->scopeable(scope_type: $scopeInfo['scope_type'], scope_id: $scopeInfo['scope_id']);
            })
            ->createOptionForm([
                Components\TextInput::make('name')->label('单位名称')->placeholder('请输入单位名称')->required(),
                Components\TextInput::make('order_column')->label('排序')->integer()
                    ->placeholder('正序排列')
                    ->rules(['integer', 'min:0'])
                    ->columnSpan(1),
            ])
            ->createOptionUsing(function(Components\Select $component, array $data) {
                $data = $component->getLivewire()->fillScopeable($data);        // 合并 scopeable 数据

                $record = $component->getRelationship()->getRelated();          // 关联的 model
                $record->fill($data);
                $record->save();                        // 保存数据
                return $data['name'];                   // 这里商品直接保存的是单位，不是 id
            })
            ->live()
            ->label('基础库存单位')
            ->placeholder('选择基础库存单位')
            ->native(false)
            ->required();
    }


    /**
     * 产品显示销量
     *
     * @return Components\TextInput
     */
    public static function showSales()
    {
        return Components\TextInput::make('show_sales')
            ->integer()
            ->suffix(fn(Get $get): ?string => $get('stock_unit'))
            ->label('显示销量');
    }


    /**
     * sku 类型
     *
     * @return Components\Radio
     */
    public static function skuType()
    {
        return Components\Radio::make('sku_type')
            ->label('规格')
            ->options(Enums\ProductSkuType::class)
            ->default(Enums\ProductSkuType::Single->value)
            // ->afterStateUpdated(fn(Set $set, ?string $state) => $set('skuPrice.sku_type', $state))
            ->live()
            ->inline();
    }



    public static function skuSimple()
    {
        return Components\Group::make()
            ->relationship('skuPrice')
            ->schema([
                static::originalPrice()->columnSpan(1),
                static::costPrice()->columnSpan(1),
                static::price()->columnSpan(1),
                static::stock()->visible(fn (Get $get): bool => $get('../stock_type') == Enums\ProductStockType::Stock->value)->columnSpan(1),
                static::weight()->columnSpan(1),
                static::productSn()->columnSpan(1),
                Components\Hidden::make('status')->default(Enums\ProductStatus::Up->value),
                Components\Hidden::make('sku_type')->default(Enums\ProductSkuType::Single->value),
            ])
            ->columns(3)
            ->columnSpanFull();
    }



    public static function skuMultiple()
    {
        return Arrange::make('sku_multiple')->label('规格')
            ->relationships([
                'arranges' => [
                    'relationship' => 'skus',
                    'childrenRelationship' => 'children',
                    'orderColumn' => 'order_column',
                    'childrenOrderColumn' => 'order_column',
                ],
                'recursions' => [
                    'relationship' => 'skuPrices',
                    'savingUsing' => function ($record, $recursion) {       // 处理 recursions 自定义字段
                        $recursion['product_sku_text'] = $recursion['arrange_texts'] ?? [];
                        $recursion['product_sn'] = $recursion['product_sn'] ?? null;
                        $recursion['image'] = $recursion['image'] ?? null;
                        $recursion['sku_type'] = $record?->sku_type ?: Enums\ProductSkuType::Single->value;
                        $recursion['original_price'] = $recursion['original_price'] ?? 0;
                        $recursion['cost_price'] = $recursion['cost_price'] ?? 0;
                        $recursion['price'] = $recursion['price'] ?? 0;
                        $recursion['stock'] = $recursion['stock'] ? intval($recursion['stock']) : 0;
                        $recursion['weight'] = $recursion['weight'] ? floatval($recursion['weight']) : 0;
                        $recursion['status'] = $recursion['status'] ?? Enums\SkuPriceStatus::Up;

                        unset($recursion['arrange_texts']);     // 删除原始字段
                        return $recursion;
                    }
                ],
            ])
            ->arrangeToRecursionKey('product_sku_ids')
            ->tableFields(function (Get $get) {
                $tableFields = [
                    // [
                    //     'label' => '图片',
                    //     'field' => 'image',
                    //     'default' => '',
                    // ],
                    [
                        'label' => '原价',
                        'field' => 'original_price',
                        'default' => 0,
                    ],
                    [
                        'label' => '成本价',
                        'field' => 'cost_price',
                        'default' => 0,
                    ],
                    [
                        'label' => '售价',
                        'field' => 'price',
                        'default' => 0,
                    ],
                ];

                if ($get('stock_type') == Enums\ProductStockType::Stock->value) {
                    $tableFields[] = [
                        'label' => '库存',
                        'field' => 'stock',
                        'default' => 0,
                        'suffix' => $get('stock_unit')
                    ];
                }
                $tableFields[] = [
                    'label' => '重量',
                    'field' => 'weight',
                    'default' => 0,
                ];
                $tableFields[] = [
                    'label' => '货号',
                    'field' => 'product_sn',
                    'default' => null,
                ];

                return $tableFields;
            })
            ->tableFieldsView('sn-product::arrange.table-fields')
            ->arrangePlaceholder('请填写规格名')
            ->arrangeChildPlaceholder('请填写子规格名')
            ->addActionLabel('添加规格')
            ->addChildActionLabel('添加子规格')
            ->required()
            ->columnSpanFull();
    }



    // /**
    //  * 产品显示库存单位
    //  *
    //  * @return Components\TextInput
    //  */
    // public static function showStockUnit()
    // {
    //     return Components\Select::make('show_stock_unit')
    //         ->label('显示库存单位')
    //         ->placeholder('选择显示库存单位')
    //         ->options(Models\UnitRepository::all()->pluck('name', 'id'))
    //         ->native(false)
    //         ->required();
    // }



    /**
     * 参数
     *
     * @return Components\KeyValue
     */
    public static function params()
    {
        return Components\KeyValue::make('params')
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
     * rich 格式内容
     *
     * @return Components\RichEditor
     */
    public static function richContent()
    {
        return Components\RichEditor::make('content')
            ->fileAttachmentsDirectory(Product::getImageDirectory())
            ->label('商品详情');
    }


    /**
     * markdown 格式内容
     *
     * @return Components\MarkdownEditor
     */
    public static function markdownContent()
    {
        return Components\MarkdownEditor::make('content')
            ->fileAttachmentsDirectory(Product::getImageDirectory())
            ->label('商品详情');
    }



    /**
     * 产品状态
     *
     * @return Components\ToggleButtons
     */
    public static function status()
    {
        return Components\ToggleButtons::make('status')
            ->default(Enums\ProductStatus::Up)
            ->inline()
            ->options(Enums\ProductStatus::class);
    }



    public static function orderColumn()
    {
        return Components\TextInput::make('order_column')->label('排序')->integer()
            ->placeholder('正序排列')
            ->rules(['integer', 'min:0']);
    }


    /**
     * 产品原价
     *
     * @return Components\TextInput
     */
    public static function originalPrice()
    {
        return Components\TextInput::make('original_price')
            ->label('原价')
            ->suffix(currency_symbol(Product::getCurrency()))
            ->numeric()
            ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/'])
            ->required();
    }


    /**
     * 产品成本价
     *
     * @return Components\TextInput
     */
    public static function costPrice()
    {
        return Components\TextInput::make('cost_price')
            ->label('成本价')
            ->suffix(currency_symbol(Product::getCurrency()))
            ->helperText('用户无法看到成本价.')
            ->numeric()
            ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/'])
            ->required();
    }


    /**
     * 产品售卖价
     *
     * @return Components\TextInput
     */
    public static function price()
    {
        return Components\TextInput::make('price')
            ->label('售卖价')
            ->suffix(currency_symbol(Product::getCurrency()))
            ->numeric()
            ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/'])
            ->required();
    }


    /**
     * 产品基础库存
     *
     * @return Components\TextInput
     */
    public static function stock()
    {
        return Components\TextInput::make('stock')
            ->integer()
            ->suffix(fn(Get $get): ?string => $get('../stock_unit'))
            ->label('库存');
    }


    /**
     * 产品重量
     *
     * @return Components\TextInput
     */
    public static function weight()
    {
        return Components\TextInput::make('weight')
            ->label('重量');
    }


    /**
     * 产品货号
     *
     * @return Components\TextInput
     */
    public static function productSn()
    {
        return Components\TextInput::make('product_sn')
            ->label('货号');
    }
}
