<?php

namespace Wsmallnews\Product\Resources\AttributeRepositoryResource\Pages;

use Wsmallnews\Product\Resources\AttributeRepositoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttributeRepository extends EditRecord
{
    protected static string $resource = AttributeRepositoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
