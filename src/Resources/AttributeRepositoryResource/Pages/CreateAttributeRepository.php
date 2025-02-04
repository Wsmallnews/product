<?php

namespace Wsmallnews\Product\Resources\AttributeRepositoryResource\Pages;

use Wsmallnews\Product\Resources\AttributeRepositoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Wsmallnews\Support\Traits\Resources\Pages\CanScopeable;

class CreateAttributeRepository extends CreateRecord
{
    use CanScopeable;

    protected static string $resource = AttributeRepositoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->fillScopeable($data);

        return $data;
    }
}
