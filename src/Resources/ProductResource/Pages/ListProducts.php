<?php

namespace Wsmallnews\Product\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;
use Wsmallnews\Product\Resources\ProductResource;
use Wsmallnews\Product\Enums\ProductStatus;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }


    public function getTabs(): array
    {
        $labels = ProductStatus::labels();

        $tabs = [
            'all' => Tab::make('all')->label('全部')
        ];
        foreach ($labels as $key => $label) {
            $tabs[$label['value']] = Tab::make($label['name'])->modifyQueryUsing(fn (Builder $query) => $query->{$label['value']}());
        }

        return $tabs;
    }
}
