<?php

namespace Wsmallnews\Product\Filament\Resources\Products\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Filament\Resources\Products\ProductResource;
use Wsmallnews\Product\Support\Utils;
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
        return [
            'all' => Tab::make()
                ->label('全部')
                ->badge(fn () => $this->getCount()),
            'up' => Tab::make()
                ->label(ProductStatus::Up->getLabel())
                ->badge(fn () => $this->getCount(ProductStatus::Up))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductStatus::Up)),
            'down' => Tab::make()
                ->label(ProductStatus::Down->getLabel())
                ->badge(fn () => $this->getCount(ProductStatus::Down))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductStatus::Down)),
            'hidden' => Tab::make()
                ->label(ProductStatus::Hidden->getLabel())
                ->badge(fn () => $this->getCount(ProductStatus::Hidden))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductStatus::Hidden)),
            'draft' => Tab::make()
                ->label(ProductStatus::Draft->getLabel())
                ->badge(fn () => $this->getCount(ProductStatus::Draft))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductStatus::Draft)),
        ];
    }

    protected function getCount(?ProductStatus $status = null): int
    {
        $query = Utils::getProductModel()::query()->snScope(
            static::getScopeType(),
            static::getScopeId(),
        );

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }
}
