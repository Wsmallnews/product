<?php

namespace Wsmallnews\Product\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Wsmallnews\Product\Enums\AttributeStatus;
use Wsmallnews\Product\Models\AttributeRepository;
use Wsmallnews\Product\Resources\AttributeRepositoryResource\Pages;

class AttributeRepositoryResource extends Resource
{
    protected static ?string $model = AttributeRepository::class;

    protected static ?string $navigationGroup = '产品管理组';
    protected static ?string $navigationLabel = '属性库管理';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = '属性';
    protected static ?string $pluralModelLabel = '属性库';

    protected static ?string $slug = '/products/attribute-repositories';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')->label('属性名称')->placeholder('请输入属性名称')->required()->columnSpan(1),
                        Forms\Components\TextInput::make('description')->label('描述')->placeholder('请输入属性描述')->columnSpan(1),
                        Forms\Components\Toggle::make('options.is_require')->label('是否必选')->columnSpanFull(),
                        Forms\Components\Toggle::make('options.is_multiple')->label('是否多选')->columnSpanFull(),
                        Forms\Components\Toggle::make('options.is_num')->live()->label('开启数量')->columnSpanFull(),
                        Forms\Components\TextInput::make('order_column')->label('排序')->integer()
                            ->placeholder('正序排列')
                            ->rules(['integer', 'min:0'])
                            ->columnSpan(1),
                        Forms\Components\ToggleButtons::make('status')
                            ->default(AttributeStatus::Up)
                            ->inline()
                            ->options(AttributeStatus::class)->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Repeater::make('children')->label('子属性')
                            ->relationship('children')
                            ->schema([
                                Forms\Components\TextInput::make('name')->hiddenLabel()->placeholder('请输入属性名称')->required()->columnSpan(1),
                                Forms\Components\TextInput::make('price')->hiddenLabel()->placeholder('请输入属性价格')
                                    ->numeric()
                                    ->prefix('￥')
                                    ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/'])
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('description')->hiddenLabel()->placeholder('请输入属性描述')->columnSpan(1),
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('options.limit_min_num')->hiddenLabel()->placeholder('起购数量')
                                            ->integer()
                                            ->required()
                                            ->rules(['integer', 'min:1'])
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('options.limit_max_num')->hiddenLabel()->placeholder('最多购买数量')
                                            ->integer()
                                            ->required()
                                            ->rules(['integer', 'min:1'])
                                            ->columnSpan(1),
                                    ])
                                    ->visible(fn (Get $get): bool => $get('../../options.is_num'))
                                    ->columns(2)->columnSpan(2),
                                Forms\Components\Select::make('status')->hiddenLabel()
                                    ->default(AttributeStatus::Up)
                                    ->options(AttributeStatus::class)->columnSpan(1),
                            ])
                            ->cloneable()
                            ->addActionLabel('添加子属性')
                            ->orderColumn('order_column')
                            ->minItems(1)
                            ->maxItems(6)
                            ->columns(6)->columnSpan(2),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('name')->label('属性名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')->label('描述')
                    ->searchable(),
                Tables\Columns\IconColumn::make('options.is_require')->label('是否必选')
                    ->alignCenter()
                    ->toggleable()
                    ->boolean(),
                Tables\Columns\IconColumn::make('options.is_multiple')->label('是否多选')
                    ->alignCenter()
                    ->toggleable()
                    ->boolean(),
                Tables\Columns\IconColumn::make('options.is_num')->label('启用数量')
                    ->alignCenter()
                    ->toggleable()
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')->label('状态')
                    ->badge()
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('order_column')->label('排序')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
                
            ->searchPlaceholder('请输入关键字')
            ->filters([
                Tables\Filters\Filter::make('is_require')
                    ->query(fn (Builder $query) => $query->whereJsonContains('options->is_require', true)),
                Tables\Filters\Filter::make('is_multiple')
                    ->query(fn (Builder $query) => $query->whereJsonContains('options->is_multiple', true)),
                Tables\Filters\Filter::make('is_num')
                    ->query(fn (Builder $query) => $query->whereJsonContains('options->is_num', true)),
                Tables\Filters\SelectFilter::make('status')
                    ->options(AttributeStatus::class),
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


    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            // Pages\ViewCustomer::class,
            Pages\EditAttributeRepository::class,
            // Pages\EditCustomerContact::class,
            // Pages\ManageCustomerAddresses::class,
            // Pages\ManageCustomerPayments::class,
        ]);
    }


    /** @return Builder<Order> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('parent_id', 0);
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
            'index' => Pages\ListAttributeRepositories::route('/'),
            'create' => Pages\CreateAttributeRepository::route('/create'),
            'edit' => Pages\EditAttributeRepository::route('/{record}/edit'),
        ];
    }
}
