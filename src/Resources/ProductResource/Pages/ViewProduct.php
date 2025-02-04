<?php

namespace Wsmallnews\Product\Resources\ProductResource\Pages;

use Wsmallnews\Product\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Wsmallnews\Support\Traits\Resources\Pages\CanScopeable;

class ViewProduct extends ViewRecord
{
    use CanScopeable;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
