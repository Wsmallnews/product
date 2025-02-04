<?php

namespace Wsmallnews\Product\Resources\UnitRepositoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Wsmallnews\Product\Resources\UnitRepositoryResource;
use Wsmallnews\Support\Traits\Resources\Pages\CanScopeable;

class ManageUnitRepositories extends ManageRecords
{
    use CanScopeable;

    protected static string $resource = UnitRepositoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data = $this->fillScopeable($data);
                    return $data;
                })
        ];
    }
}
