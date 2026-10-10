<?php

namespace Wsmallnews\Product\Filament\Resources\Extras\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Wsmallnews\Product\Filament\Resources\Extras\ExtraResource;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class EditExtra extends EditRecord
{
    use Scopeable;

    protected static string $resource = ExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
