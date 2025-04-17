<?php

namespace Wsmallnews\Product\Repositories;

use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Wsmallnews\Product\Product;
use Wsmallnews\Support\Filament\Tables\Columns\MediableImageColumn;

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


    public static function image()
    {
        return MediableImageColumn::make('image')->label('产品主图')
            ->tag('main')
            ->variant('thumbnail');


        // return Tables\Columns\SpatieMediaLibraryImageColumn::make('image')
        //     ->conversion('small')
        //     ->collection('main')
        //     ->label('图片');


        // return Tables\Columns\ImageColumn::make('image')->label('图片');
    }


    public static function images()
    {
        return MediableImageColumn::make('images')->label('产品轮播图')
            ->tag('gallery')
            ->variant('thumbnail')
            ->limit(3)
            ->limitedRemainingText(isSeparate: true);


        // return Tables\Columns\SpatieMediaLibraryImageColumn::make('image')
        //     ->conversion('small')
        //     ->collection('main')
        //     ->label('图片');


        // return Tables\Columns\ImageColumn::make('image')->label('图片');
    }

    
    public static function title($showdescription = false)
    {
        return Tables\Columns\TextColumn::make('title')
            ->description(fn($record) => $showdescription ? $record->subtitle : null)
            ->limit(50)
            ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                $state = $column->getState();

                if (strlen($state) <= $column->getCharacterLimit()) {
                    return null;
                }

                // Only render the tooltip if the column content exceeds the length limit.
                return $state;
            })
            // ->lineClamp(3)
            ->label('标题');
    } 

    public static function type()
    {
        return Tables\Columns\TextColumn::make('type')->label('类型');
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

    public static function createdAt()
    {
        return Tables\Columns\TextColumn::make('created_at')
            ->since()
            ->dateTimeTooltip()
            ->label('创建时间');
    }


    public static function updatedAt()
    {
        return Tables\Columns\TextColumn::make('updated_at')
            ->since()
            ->dateTimeTooltip()
            ->label('更新时间');
    }


    public static function status()
    {
        return Tables\Columns\TextColumn::make('status')->label('状态');
    }
}
