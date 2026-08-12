<?php

namespace Wsmallnews\Product\Filament\Resources\Products\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Forms;
use Filament\Schemas;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Enums\ProductSkuType;
use Wsmallnews\Support\Filament\Actions\ActionComponents;
use Wsmallnews\Support\Filament\Filters\FilterComponents;
use Wsmallnews\Support\Filament\Resources\ScheduledTasks\Concerns\ViewScheduledTasksAction;
use Wsmallnews\Support\Filament\Tables\ColumnComponents;

class ProductTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                static::IDColumn(),
                static::productInfoColumn(),
                // static::originalPriceColumn(),
                // static::priceColumn(),
                // static::viewNumColumn(),
                static::statusColumn(),
                static::createdAtColumn(),
                static::updatedAtColumn(),
            ])
            ->reorderable('order_column')
            ->defaultSort('order_column', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['media']))
            ->searchPlaceholder('搜索产品标题')
            ->filtersFormWidth(Width::Medium)
            ->filters([
                static::statusFilter(),
                // static::skuTypeFilter(),
                static::priceRangeFilter(),
                ...FilterComponents::createUpdateRangeFilter(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ...ActionComponents::recordActions([
                    ViewAction::make(),
                    EditAction::make(),
                    ViewScheduledTasksAction::make()->color('info'),
                    DeleteAction::make(),
                    ForceDeleteAction::make()
                        ->before(function (Model $record) {
                            // 强制删除时，先删除关联的分类
                            $record->categories()->delete();
                        }),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                ...ActionComponents::toolbarActions([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            // $records->each(function ($record) {
                            //     // 强制删除时，先删除关联的分类
                            //     $record->categories()->delete();
                            // });
                        }),
                    RestoreBulkAction::make()
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->striped();
    }

    // ========================= Columns =========================

    protected static function IDColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->alignCenter()
            ->toggleable();
    }

    protected static function productInfoColumn(): Tables\Columns\TextColumn
    {
        return ColumnComponents::modelColumn(
            'title',
            '产品信息',
            fn ($record) => $record,
        )->searchable(['title', 'description']);
    }

    // protected static function originalPriceColumn(): Tables\Columns\TextColumn
    // {
    //     return Tables\Columns\TextColumn::make('original_price')
    //         ->label('原价')
    //         ->sortable()
    //         ->alignLeft()
    //         ->toggleable()
    //         ->extraAttributes(['style' => 'text-decoration-line: line-through']);
    // }

    // protected static function priceColumn(): Tables\Columns\TextColumn
    // {
    //     return Tables\Columns\TextColumn::make('price')
    //         ->label('售价')
    //         ->sortable()
    //         ->alignLeft()
    //         ->toggleable();
    // }

    // protected static function viewNumColumn(): Tables\Columns\TextColumn
    // {
    //     return Tables\Columns\TextColumn::make('view_num')
    //         ->label('浏览量')
    //         ->sortable()
    //         ->alignLeft()
    //         ->toggleable();
    // }

    protected static function createdAtColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('created_at')
            ->label('创建时间')
            ->toggleable()
            ->sortable();
    }

    protected static function updatedAtColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('updated_at')
            ->label('更新时间')
            ->toggleable()
            ->sortable();
    }

    protected static function statusColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('status')
            ->label('状态')
            ->badge()
            ->formatStateUsing(fn (ProductStatus $state): string => $state->getLabel())
            ->color(fn (ProductStatus $state): string => $state->getColor())
            ->icon(fn (ProductStatus $state): ?string => $state->getIcon())
            ->toggleable();
    }

    // ========================= Filters =========================

    protected static function statusFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('status')
            ->label('状态')
            ->options(ProductStatus::class)
            ->multiple();
    }

    protected static function skuTypeFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('sku_type')
            ->label('规格类型')
            ->options(ProductSkuType::class);
    }

    protected static function priceRangeFilter(): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make('price_range')
            ->label('价格区间')
            ->schema([
                Schemas\Components\Group::make([
                    Forms\Components\TextInput::make('price_start')
                        ->label('最低价')
                        ->numeric()
                        ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/']),
                    Forms\Components\TextInput::make('price_end')
                        ->label('最高价')
                        ->numeric()
                        ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/']),
                ])->columns(2),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['price_start'],
                        fn (Builder $query, string $priceStart): Builder => $query->where('price', '>=', round((float) $priceStart * 100)),
                    )
                    ->when(
                        $data['price_end'],
                        fn (Builder $query, string $priceEnd): Builder => $query->where('price', '<=', round((float) $priceEnd * 100)),
                    );
            });
    }
}
