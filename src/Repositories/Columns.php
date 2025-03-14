<?php

namespace Wsmallnews\Product\Repositories;

use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Wsmallnews\Product\Product;

class Columns
{

    public static function id()
    {
        return Tables\Columns\TextColumn::make('id')->label('Id')
                ->sortable();
    }


    public static function productInfo()
    {
        return Tables\Columns\ViewColumn::make('product_info')->label('产品信息')
                ->searchable(['title', 'subtitle'])
                ->grow()
                ->view('sn-product::tables.columns.product-card');
    }



    public static function originalPrice()
    {
        return Tables\Columns\TextColumn::make('original_price')->label('商品原价')
                // ->money()
                ->sortable()
                ->alignLeft()
                ->extraAttributes(['style' => 'text-decoration-line: line-through']);
    }


    public static function price()
    {
        return Tables\Columns\TextColumn::make('price')->label('商品售价')
                // ->money()
                ->sortable()
                ->weight(FontWeight::Bold)
                ->alignLeft();
    }


    public static function viewNum()
    {
        return Tables\Columns\TextColumn::make('view_num')->label('浏览量')
                ->sortable()
                ->alignLeft();
    }


    public static function updatedAt()
    {
        return Tables\Columns\TextColumn::make('updated_at')->label('更新时间');
    }


    public static function status()
    {
        return Tables\Columns\TextColumn::make('status')->label('状态');
    }
}
