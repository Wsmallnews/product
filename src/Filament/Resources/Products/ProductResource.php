<?php

namespace Wsmallnews\Product\Filament\Resources\Products;

use Closure;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Wsmallnews\Product\Filament\Resources\Products\Pages\CreateProduct;
use Wsmallnews\Product\Filament\Resources\Products\Pages\EditProduct;
use Wsmallnews\Product\Filament\Resources\Products\Pages\ListProducts;
use Wsmallnews\Product\Filament\Resources\Products\Pages\ViewProduct;
use Wsmallnews\Product\ProductPlugin;
use Wsmallnews\Support\Filament\Concerns\CanBeConfigured;
use Wsmallnews\Support\Filament\Resources\ResourceConfiguration;

final class ProductResource extends BaseResource
{
    use CanBeConfigured;

    protected static ?string $configurationClass = ResourceConfiguration::class;

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
            'view' => ViewProduct::route('/{record}'),
        ];
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
