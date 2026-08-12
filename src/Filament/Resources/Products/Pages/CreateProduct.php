<?php

namespace Wsmallnews\Product\Filament\Resources\Products\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Filament\Resources\Products\ProductResource;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class CreateProduct extends CreateRecord
{
    use Scopeable;

    protected static string $resource = ProductResource::class;


    /**
     * Mutate the form data before creating a record.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 合并 scopeinfo 参数
        $data = array_merge($data, static::getScopeable());

        // 合并 publisher 参数
        $admin = Filament::auth()->user();
        $data = array_merge($data, [
            'publisher_type' => $admin->getMorphClass(),
            'publisher_id' => $admin->id,
        ]);

        if (in_array($data['status'], [ProductStatus::Up, ProductStatus::Hidden])) {
            $data['published_at'] = now();
        }

        return parent::mutateFormDataBeforeCreate($data);
    }
}
