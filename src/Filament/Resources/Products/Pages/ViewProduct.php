<?php

namespace Wsmallnews\Product\Filament\Resources\Products\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Wsmallnews\Product\Filament\Resources\Products\ProductResource;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class ViewProduct extends ViewRecord
{
    use Scopeable;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
