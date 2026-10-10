<?php

namespace Wsmallnews\Product\Filament\Resources\Extras\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wsmallnews\Product\Filament\Resources\Extras\ExtraResource;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class ListExtras extends ListRecords
{
    use Scopeable;

    protected static string $resource = ExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
