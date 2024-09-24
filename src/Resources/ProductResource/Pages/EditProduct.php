<?php

namespace Wsmallnews\Product\Resources\ProductResource\Pages;

use Wsmallnews\Product\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;


    public function boot(): void
    {
        // db_listen();
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
