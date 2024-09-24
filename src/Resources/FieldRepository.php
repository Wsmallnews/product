<?php

namespace Wsmallnews\Product;

use Filament\Forms\Components;
use Wsmallnews\Product\Enums;
use Wsmallnews\Product\Models;

class FieldRepositories
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
            ->directory(config('sn-product.image_directory'))
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
            ->directory(config('sn-product.image_directory'))
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
     * 产品原价
     *
     * @return Components\TextInput
     */
    public static function originalPrice()
    {
        return Components\TextInput::make('original_price')
            ->label('原价')
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
            ->numeric()
            ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/'])
            ->required();
    }



    public static function skuInfos()
    {
        return Arrange::make('sku_infos')->label('规格')
            ->arrangeToRecursionKey('product_sku_ids')
            ->tableFields([
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
            ])
            ->afterStateHydrated(function (Arrange $component, $record) {
                $record->load('skus.children', 'skuPrices');
                $state = collect([
                    'arranges' => $record->skus,
                    'recursions' => $record->skuPrices,
                ]);
                $component->state($state);
            })
            ->dehydrateStateUsing(function ($state) {
                return $state->toArray();
            })
            ->arrangePlaceholder('请填写规格名')
            ->arrangeChildPlaceholder('请填写子规格名')
            ->addActionLabel('添加规格')
            ->addChildActionLabel('添加子规格');
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
            ->live()
            ->inline();
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
            ->label('基础库存单位')
            ->placeholder('选择基础库存单位')
            ->options(Models\UnitRepository::all()->pluck('name', 'id'))
            ->native(false)
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
            ->label('库存');
    }


    /**
     * 产品基础库存
     *
     * @return Components\TextInput
     */
    public static function showSales()
    {
        return Components\TextInput::make('show_sales')
            ->label('显示销量');
    }


    /**
     * 产品显示库存单位
     *
     * @return Components\TextInput
     */
    public static function showStockUnit()
    {
        return Components\Select::make('show_stock_unit')
            ->label('显示库存单位')
            ->placeholder('选择显示库存单位')
            ->options(Models\UnitRepository::all()->pluck('name', 'id'))
            ->native(false)
            ->required();
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


    /**
     * 规格项
     *
     * @return Components\Repeater
     */
    public static function skus()
    {
        return Components\Repeater::make('skus')
            ->label('规格项')
            ->relationship('skus')
            ->schema([
                Components\TextInput::make('name')->hiddenLabel()->live()->placeholder('请输入规格名')->columnSpan(1),
                Components\Fieldset::make('children_groupon')
                    ->label('规格值')
                    ->schema([
                        Components\Repeater::make('children')
                            ->relationship('children')
                            ->simple(
                                Components\TextInput::make('name')->hiddenLabel()->live()
                                    ->placeholder('请输入规格值')
                                    ->columnSpanFull(),
                            )
                            ->mutateRelationshipDataBeforeCreateUsing(function (?Model $productSku, array $data): array {
                                $data['product_id'] = $productSku->product_id;
                                return $data;
                            })
                            ->orderColumn('order_column')
                            ->defaultItems(1)
                            ->hiddenLabel()
                            ->grid(4)
                            ->addActionLabel('添加规格值')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
            ])
            ->defaultItems(1)
            ->minItems(1)
            ->maxItems(3)
            ->addActionLabel('添加规格项')
            ->orderColumn('order_column')
            ->columns(4)
            ->columnSpanFull();
    }


    /**
     * 规格价格
     *
     * @return Components\Section
     */
    public static function skuPrices()
    {
        // $a = [1,2,3];

        // $skus = $get('skus');
        // $descartes = [];
        // foreach ($skus as $sku) {
        //     if (blank($sku['name'])) {
        //         continue;
        //     }

        //     foreach ($sku['childrens'] as $children) {
        //         if (blank($sku['name'])) {
        //             continue;
        //         }


        //     }
        // }

        // foreach ($a as $v) {
        //     $skuPrices[] = Forms\Components\Group::make()->schema([
        //             Forms\Components\TextInput::make('name')->columnSpan(1),
        //             Forms\Components\TextInput::make('price')->columnSpan(1),
        //         ])
        //         ->columns(9)
        //         ->columnSpanFull();
        // }

        return Components\Section::make()
            ->schema(function (Forms\Get $get) {
                $skus = $get('skus');
                self::$skuPrices = [];

                $skuChildrenIdArr = [];
                foreach ($skus as $key => $sku) {
                    $childrenIdArr = [];
                    if ($sku['childrens']) {
                        foreach ($sku['childrens'] as $k => $v) {
                            $childrenIdArr[] = $k;
                        }

                        $skuChildrenIdArr[] = $childrenIdArr;
                    }
                }

                self::recursionSku($skus, $skuChildrenIdArr);


                $skuPrices = self::$skuPrices;



                $skuPricesForms = [];


                foreach ($skuPrices as $skuPrice) {
                    $skuPricesForms[] = Components\Group::make()->schema([
                        Components\TextInput::make('name')->placeholder(join(',', $skuPrice['product_sku_text']))->columnSpan(1),
                        Components\TextInput::make('price')->columnSpan(1),
                    ])
                    ->columns(9)
                    ->columnSpanFull();
                }
                return $skuPricesForms;
            });
    }


    protected static $skuPrices = [];

    private static function recursionSku($skus, $skuChildrenIdArr, $skuK = 0, $temp = [])
    {
        //递归找笛卡尔规格集合
        if ($skuK == count($skuChildrenIdArr) && $skuK != 0) {
            $tempDetail = [];
            $tempDetailIds = [];

            foreach ($temp as $key => $item) {
                foreach ($skus as $skuKey => $sku) {
                    foreach ($sku['childrens'] as $childKey => $child) {
                        if ($item == $childKey) {
                            $tempDetail[] = $child['name'];
                            $tempDetailIds[] = $childKey;               // 唯一 id
                        }
                    }
                }
            }

            $flag = false;
            foreach (self::$skuPrices as $priceKey => $skuPrice) {
                if (join(',', $skuPrice['product_sku_temp_ids']) == join(',', $tempDetailIds)) {
                    $flag = $priceKey;
                    break;
                }
            }

            if (!$flag) {
                self::$skuPrices[] = [
                    'price' => 0,
                    'product_sku_ids' => '',
                    'product_id' => 0,
                    'product_sku_text' => $tempDetail,
                    'product_sku_temp_ids' => $tempDetailIds,
                ];
            } else {
                self::$skuPrices[$flag]['product_sku_text'] = $tempDetail;
                self::$skuPrices[$flag]['product_sku_temp_ids'] = $tempDetailIds;
            }

            return;
        }

        if ($skuChildrenIdArr) {
            foreach ($skuChildrenIdArr[$skuK] as $ck => $cv) {
                $temp[$skuK] = $skuChildrenIdArr[$skuK][$ck];
                self::recursionSku($skus, $skuChildrenIdArr, $skuK + 1, $temp);
            }
        }
    }




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
            >label('商品详情');
    }


    /**
     * markdown 格式内容
     *
     * @return Components\MarkdownEditor
     */
    public static function markdownContent()
    {
        return Components\MarkdownEditor::make('content')
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
}
