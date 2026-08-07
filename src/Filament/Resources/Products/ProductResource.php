<?php

namespace Wsmallnews\Product\Filament\Resources\Products;

use Closure;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Wsmallnews\Product\Enums\FormLayout;
use Wsmallnews\Product\Filament\Resources\Products\Pages\CreateProduct;
use Wsmallnews\Product\Filament\Resources\Products\Pages\CreateProductWizard;
use Wsmallnews\Product\Filament\Resources\Products\Pages\EditProduct;
use Wsmallnews\Product\Filament\Resources\Products\Pages\EditProductWizard;
use Wsmallnews\Product\Filament\Resources\Products\Pages\ListProducts;
use Wsmallnews\Product\Filament\Resources\Products\Pages\ViewProduct;
use Wsmallnews\Product\ProductPlugin;
use Wsmallnews\Product\Support\Utils;
use Wsmallnews\Support\Filament\Concerns\CanBeConfigured;
use Wsmallnews\Support\Filament\Resources\ResourceConfiguration;

final class ProductResource extends BaseResource
{
    use CanBeConfigured;

    protected static ?string $configurationClass = ResourceConfiguration::class;

    public static function getPages(): array
    {
        $layout = FormLayout::tryFrom(Utils::getConfig('form_layout.layout', FormLayout::Plain->value));

        return match ($layout) {
            FormLayout::Wizard => [
                'index' => ListProducts::route('/'),
                'create' => CreateProductWizard::route('/create'),
                'edit' => EditProductWizard::route('/{record}/edit'),
                'view' => ViewProduct::route('/{record}'),
            ],
            default => [
                'index' => ListProducts::route('/'),
                'create' => CreateProduct::route('/create'),
                'edit' => EditProduct::route('/{record}/edit'),
                'view' => ViewProduct::route('/{record}'),
            ],
        };
    }

    public static function form(Schema $schema): Schema
    {
        $resolveForm = self::resolveCustomProperty('form');

        return $resolveForm instanceof Closure ? $resolveForm($schema, self::class) : parent::form($schema);
    }

    public static function table(Table $table): Table
    {
        $resolveTable = self::resolveCustomProperty('table');

        return $resolveTable instanceof Closure ? $resolveTable($table, self::class) : parent::table($table);
    }

    public static function getEssentialsPlugin(): ?ProductPlugin
    {
        return ProductPlugin::get();
    }
}
