<?php

namespace Wsmallnews\Product\Filament\Resources\Products\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Wsmallnews\Product\Filament\Resources\Products\ProductResource;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class EditProduct extends EditRecord
{
    use Scopeable;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
