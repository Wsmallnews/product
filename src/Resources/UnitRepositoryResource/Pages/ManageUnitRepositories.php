<?php

namespace Wsmallnews\Product\Resources\UnitRepositoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Wsmallnews\Product\Resources\UnitRepositoryResource;

class ManageUnitRepositories extends ManageRecords
{
    protected static string $resource = UnitRepositoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
