<?php

namespace Wsmallnews\Product\Resources\AttributeRepositoryResource\Pages;

use Wsmallnews\Product\Resources\AttributeRepositoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Wsmallnews\Support\Traits\Resources\Pages\CanScopeable;

class ListAttributeRepositories extends ListRecords
{
    use CanScopeable;

    protected static string $resource = AttributeRepositoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
