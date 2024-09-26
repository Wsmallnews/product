<?php

namespace Wsmallnews\Product\FieldsLayout;

use Filament\Forms\Components;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Wsmallnews\Product\Contracts\LayoutInterface;
use Wsmallnews\Product\Enums;
use Wsmallnews\Product\FieldRepository;

class NormalLayout implements LayoutInterface
{

    public function schema(): array
    {
        return [
            Components\Group::make()
                ->schema([
                    Components\Group::make()
                        ->schema([
                            Components\Section::make()
                                ->schema([
                                    FieldRepository::title()->columnSpan(1),
                                    FieldRepository::subtitle()->columnSpan(1)
                                ])->columns(2),

                            Components\Section::make('图片组')
                                ->schema([
                                    FieldRepository::image()->columnSpan(1),
                                    FieldRepository::images()->columnSpan(1)
                                ])->columns(2),

                            
                            Components\Section::make('规格库存')
                                ->schema([
                                    FieldRepository::stockType()->columnSpan(1),
                                    FieldRepository::stockUnit()->columnSpan(1),
                                    FieldRepository::showSales()->columnSpan(1),


                                    FieldRepository::skuType()
                                        ->disabledOn(['edit'])      // 编辑时禁止修改 规格类型
                                        ->columnSpanFull(),

                                    FieldRepository::skuSimple()
                                        ->visible(function (Get $get) {
                                            return $get('sku_type') == Enums\ProductSkuType::Single->value;
                                        }),

                                    FieldRepository::skuMultiple()
                                        ->visible(function (Get $get) {
                                            return $get('sku_type') == Enums\ProductSkuType::Multiple->value;
                                        })
                                        ->columnSpanFull(),
                                ])->columns(2),

                            // Components\Section::make('库存销量')
                            //     ->schema([
                                    
                            //     ])->columns(2),

                            Components\Section::make('参数详情')
                                ->schema([
                                    FieldRepository::params()->columnSpanFull(),
                                    FieldRepository::markdownContent()->columnSpanFull()
                                ])->columns(2),
                        ])->columnSpan(2),
                    Components\Group::make()
                        ->schema([
                            Components\Section::make('状态')
                                ->schema([
                                    FieldRepository::status()->columnSpanFull(),
                                    FieldRepository::orderColumn()->columnSpanFull()
                                ])->columns(2),
                        ])->columnSpan(1)
                ])->columns(3)->columnSpanFull()
        ];
    }
}
