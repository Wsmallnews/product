<?php

namespace Wsmallnews\Product\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Wsmallnews\Product\Models\Product as ProductModel;
use Wsmallnews\Product\Resources\ProductResource\Pages;
use Wsmallnews\Product\ResourceBuilder\ProductResourceBuilder;

class ProductResource extends Resource
{
    protected static ?string $model = ProductModel::class;

    protected static ?string $navigationGroup = '产品管理';

    protected static ?string $navigationLabel = '产品库';
    protected static ?string $navigationIcon = 'elemplus-goods-filled';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = '产品';
    protected static ?string $pluralModelLabel = '产品库';

    protected static ?string $slug = '/products';

    protected static ?int $navigationSort = 1;



    public static function setAttribute($key, $value)
    {
        self::$$key = $value;
    }



    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                (new ProductResourceBuilder)->schema(),
            );
    }



    // 字段排序
    // filter 排序
    // 列搜索，或者筛选
    // 是否清空选中列复选框
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                (new ProductResourceBuilder)->columns()
            )
            ->filters(
                (new ProductResourceBuilder)->filters(),
                layout: \Filament\Tables\Enums\FiltersLayout::AboveContentCollapsible       // 这个更好，可以在表格上面展示搜索条件，可以折叠
            )
            ->deferFilters()        // 延迟过滤
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->searchPlaceholder('搜索产品标题')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }


    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() > 100 ? 'warning' : 'primary';
    }
}
