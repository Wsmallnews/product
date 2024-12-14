<?php

namespace Wsmallnews\Product\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\CreateRecord;
use Wsmallnews\Product\FieldRepository;
use Wsmallnews\Product\Enums;
use Wsmallnews\Product\ResourceBuilder\ProductResourceBuilder;
use Wsmallnews\Product\ResourceBuilder\Traits\WizardForm;
use Wsmallnews\Product\Resources\ProductResource;
class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;


    use WizardForm;

    protected function hasSkippableSteps(): bool
    {
        return true;
    }
}
