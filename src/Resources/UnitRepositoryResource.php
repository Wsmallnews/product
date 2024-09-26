<?php

namespace Wsmallnews\Product\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Wsmallnews\Product\Models\UnitRepository;
use Wsmallnews\Product\Resources\UnitRepositoryResource\Pages;
use Wsmallnews\Product\Resources\UnitRepositoryResource\RelationManagers;

class UnitRepositoryResource extends Resource
{
    protected static ?string $model = UnitRepository::class;

    protected static ?string $navigationGroup = '产品管理组';
    protected static ?string $navigationLabel = '单位库管理';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = '单位';
    protected static ?string $pluralModelLabel = '单位库';

    protected static ?string $slug = '/products/unit-repositories';

    protected static ?int $navigationSort = 3;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')->label('单位名称')->placeholder('请输入单位名称')->required()->columnSpan(1),
                        Forms\Components\TextInput::make('order_column')->label('排序')->integer()
                            ->placeholder('正序排列')
                            ->rules(['integer', 'min:0'])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('name')->label('单位名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_column')->label('排序')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUnitRepositories::route('/'),
        ];
    }
}
