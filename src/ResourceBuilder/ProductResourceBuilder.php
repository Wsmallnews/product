<?php

namespace Wsmallnews\Product\ResourceBuilder;

use Filament\Tables;
use Filament\Forms\Components;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Get;
use Wsmallnews\Product\Contracts\ResourceBuilderInterface;
use Wsmallnews\Product\Enums;
use Wsmallnews\Product\Repositories\Fields as FieldsRepository;
use Wsmallnews\Product\Repositories\Columns as ColumnsRepository;
use Wsmallnews\Product\Repositories\Filters as FiltersRepository;


class ProductResourceBuilder implements ResourceBuilderInterface
{

    public function schema(): array
    {
        return [
            Components\Group::make()
                ->schema([
                    Components\Group::make()
                        ->schema([
                            Components\Section::make()
                                ->schema($this->baseInfo())->columns(2),

                            Components\Section::make('规格库存')
                                ->schema($this->skuStockInfo())->columns(2),

                            Components\Section::make('参数详情')
                                ->schema($this->detailInfo())->columns(2),
                        ])->columnSpan(2),
                    Components\Group::make()
                        ->schema([
                            Components\Section::make('状态')
                                ->schema($this->statusInfo())->columns(2),
                        ])->columnSpan(1)
                ])
                ->columns(3)
                ->columnSpanFull()
        ];
    }



    public function getWizardSteps(): array
    {
        return [
            Wizard\Step::make('基础信息')
                ->icon('heroicon-o-home')
                ->completedIcon('heroicon-m-hand-thumb-up')
                ->schema([
                    Components\Section::make()->schema(
                        $this->baseInfo()
                    )
                ]),
            Wizard\Step::make('规格库存')
                ->icon('ionicon-pricetags')
                ->completedIcon('heroicon-m-hand-thumb-up')
                ->schema([
                    Components\Section::make()->schema(
                        $this->skuStockInfo()
                    )
                ]),
            Wizard\Step::make('产品详情')
                ->icon('mdi-content-save-edit')
                ->completedIcon('heroicon-m-hand-thumb-up')
                ->schema([
                    Components\Section::make()->schema(
                        $this->detailInfo()
                    )
                ]),
        ];
    }


    public function columns(): array
    {
        return [
            ColumnsRepository::id(),
            ColumnsRepository::productInfo(),
            ColumnsRepository::originalPrice(),
            ColumnsRepository::price(),
            ColumnsRepository::viewNum(),
            ColumnsRepository::updatedAt(),
            ColumnsRepository::status(),
        ];
    }


    public function filters(): array
    {
        return [
            FiltersRepository::priceRange(),
            Tables\Filters\TrashedFilter::make(),
        ];
    }




    public function baseInfo(): array
    {
        return [
            FieldsRepository::title()->columnSpan(2),
            FieldsRepository::subtitle()->columnSpan(2),
            FieldsRepository::image()->columnSpan(1),
            FieldsRepository::images()->columnSpan(2),
        ];
    }


    public function skuStockInfo(): array
    {
        return [
            FieldsRepository::stockType()->columnSpan(1),
            FieldsRepository::stockUnit()->columnSpan(1),
            FieldsRepository::showSales()->columnSpan(1),

            FieldsRepository::skuType()
                // ->disabledOn(['edit'])      // 编辑时禁止修改 规格类型
                ->columnSpanFull(),

            FieldsRepository::skuSimple()
                ->visible(function (Get $get) {
                    return $get('sku_type') == Enums\ProductSkuType::Single->value;
                }),

            FieldsRepository::skuMultiple()
                ->visible(function (Get $get) {
                    return $get('sku_type') == Enums\ProductSkuType::Multiple->value;
                })
                ->columnSpanFull(),
        ];
    }



    public function detailInfo(): array
    {
        return [
            FieldsRepository::params()->columnSpanFull(),
            FieldsRepository::richContent()->columnSpanFull()
        ];
    }


    public function statusInfo(): array
    {
        return [
            FieldsRepository::status()->columnSpanFull(),
            FieldsRepository::orderColumn()->columnSpanFull()
        ];
    }
}
