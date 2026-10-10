<?php

namespace Wsmallnews\Product\Filament\Resources\Extras;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use Wsmallnews\Product\Filament\Resources\Extras\Schemas\ExtraForm;
use Wsmallnews\Product\Filament\Resources\Extras\Tables\ExtrasTable;
use Wsmallnews\Product\Support\Utils;
use Wsmallnews\Support\Filament\Resources\Concerns\Scopeable;

abstract class BaseResource extends Resource
{
    use Scopeable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string | BackedEnum | null $activeNavigationIcon = Heroicon::Sparkles;

    protected static ?string $slug = 'product-extras';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function getModel(): string
    {
        return Utils::getExtraModel();
    }

    public static function getModelLabel(): string
    {
        return static::$modelLabel ?? __('sn-product::product.extra_resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return static::$pluralModelLabel ?? __('sn-product::product.extra_resource.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? __('sn-product::product.extra_resource.navigation_label');
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return static::$navigationGroup ?? __('sn-product::product.global_default.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return ExtraForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExtrasTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::applyScopeableToQuery(parent::getEloquentQuery())
            // 列表只显示组行（选项在表单内联维护）；软删记录经 TrashedFilter 查看
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('parent_id', 0);
    }
}
