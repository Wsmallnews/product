<?php

namespace Wsmallnews\Product\Filament\Resources\Products\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Filament\Resources\Products\ProductResource;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class ListProducts extends ListRecords
{
    use Scopeable;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make()
                ->label(__('sn-product::product.product_resource.tabs.all'))
                ->badge(fn (): int => $this->getCount()),
        ];

        foreach (ProductStatus::cases() as $status) {
            $tabs[$status->value] = Tab::make()
                ->label($status->getLabel())
                ->badge(fn (): int => $this->getCount($status))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', $status));
        }

        return $tabs;
    }

    protected function getCount(?ProductStatus $status = null): int
    {
        $query = static::getResource()::getEloquentQuery()->withoutTrashed();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }
}
