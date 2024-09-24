<?php

namespace Wsmallnews\Product\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Attributes\Url;
use Wsmallnews\Product\Enums;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Resources\ProductResource\Pages;
use Wsmallnews\Product\FieldsLayout\NormalLayout;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = '产品管理组';
    protected static ?string $navigationLabel = '产品管理';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = '产品';
    protected static ?string $pluralModelLabel = '产品库';

    // protected static ?string $slug = '/products';
    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                (new NormalLayout)->schema(),
            );
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Id')
                    ->sortable(),
                Tables\Columns\ViewColumn::make('product_info')->label('产品信息')
                    ->searchable(['title', 'subtitle'])
                    ->grow()
                    ->view('sn-product::tables.columns.product-card'),
                Tables\Columns\TextColumn::make('original_price')->label('商品原价')
                    ->money(config('sn-product.currency'))
                    ->sortable()
                    ->alignLeft()
                    ->extraAttributes(['style' => 'text-decoration-line: line-through']),
                Tables\Columns\TextColumn::make('price')->label('商品售价')
                    ->money(config('sn-product.currency'))
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->alignLeft(),
                Tables\Columns\TextColumn::make('view_num')->label('浏览量')
                    ->sortable()
                    ->alignLeft(),
                Tables\Columns\TextColumn::make('updated_at')->label('更新时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')->label('状态'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\TextInput::make('price_start')
                            ->numeric()
                            ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/']),
                        Forms\Components\TextInput::make('price_end')
                            ->numeric()
                            ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/'])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_start'],
                                fn (Builder $query, $price_start): Builder => $query->where('price', '>=', round(floatval($price_start) * 100)),
                            )
                            ->when(
                                $data['price_end'],
                                fn (Builder $query, $price_end): Builder => $query->where('price', '<=', round(floatval($price_end) * 100)),
                            );
                    })

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            //
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
