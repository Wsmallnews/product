<?php

namespace Wsmallnews\Product\Repositories;

use Filament\Forms\Components;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class Filters
{


    public static function priceRange()
    {
        return Tables\Filters\Filter::make('price_range')
            ->form([
                Components\TextInput::make('price_start')
                    ->numeric()
                    ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/']),
                Components\TextInput::make('price_end')
                    ->numeric()
                    ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/'])
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['price_start'],
                        fn(Builder $query, $price_start): Builder => $query->where('price', '>=', round(floatval($price_start) * 100)),
                    )
                    ->when(
                        $data['price_end'],
                        fn(Builder $query, $price_end): Builder => $query->where('price', '<=', round(floatval($price_end) * 100)),
                    );
            });
    }
}
