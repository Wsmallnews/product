<?php

namespace Wsmallnews\Product\Filament\Resources\Extras\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Wsmallnews\Product\Enums\ExtraStatus;
use Wsmallnews\Support\Filament\Actions\ActionComponents;
use Wsmallnews\Support\Filament\Filters\FilterComponents;

class ExtrasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->searchable()->sortable()->alignCenter()->toggleable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('sn-product::product.extra_table.name'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('sn-product::product.extra_table.description'))
                    ->limit(30)
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('sn-product::product.extra_table.type'))
                    ->badge(),

                Tables\Columns\IconColumn::make('is_require')
                    ->label(__('sn-product::product.extra_table.is_require'))
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_num')
                    ->label(__('sn-product::product.extra_table.is_num'))
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('children_count')
                    ->counts('children')
                    ->label(__('sn-product::product.extra_table.children_count'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('sn-product::product.extra_table.status'))
                    ->badge(),

                Tables\Columns\TextColumn::make('order_column')
                    ->label(__('sn-product::product.extra_table.order_column'))
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('sn-cms::cms.link_table.created_at'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('sn-product::product.extra_table.updated_at'))
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // 配置型业务域：新记录追加到末尾，拖拽与默认排序方向一致
            ->reorderable('order_column', direction: 'asc')
            ->defaultSort('order_column', 'asc')
            ->searchPlaceholder(__('sn-product::product.extra_table.search_placeholder'))
            ->filtersFormWidth(Width::Medium)
            ->filters([
                FilterComponents::statusFilter(ExtraStatus::class),
                ...FilterComponents::createUpdateRangeFilter(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ...ActionComponents::recordActions([
                    EditAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                ...ActionComponents::toolbarActions([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
