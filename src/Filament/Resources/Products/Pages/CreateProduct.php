<?php

namespace Wsmallnews\Product\Filament\Resources\Products\Pages;

use Filament\Resources\Pages\CreateRecord;
use Wsmallnews\Product\Filament\Resources\Products\ProductResource;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class CreateProduct extends CreateRecord
{
    use Scopeable;

    protected static string $resource = ProductResource::class;
}
