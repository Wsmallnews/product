<?php

namespace Wsmallnews\Product\Filament\Resources\Extras\Schemas;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Wsmallnews\Product\Enums\ExtraStatus;
use Wsmallnews\Product\Enums\ExtraType;
use Wsmallnews\Support\Filament\Forms\FormComponents;

class ExtraForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Section::make()->schema(static::groupFields())->columns(2)->columnSpanFull(),
                Schemas\Components\Section::make(__('sn-product::product.extra_form.options_section'))
                    ->schema(static::optionFields())
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * 组行字段（选择规则：单选/多选、必选、数量计价 + 永久上下架）。
     */
    public static function groupFields(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label(__('sn-product::product.extra_form.name'))
                ->required()
                ->columnSpan(1),

            Forms\Components\TextInput::make('description')
                ->label(__('sn-product::product.extra_form.description'))
                ->columnSpan(1),

            FormComponents::enumsToggleButtons(ExtraType::class, 'type')
                ->label(__('sn-product::product.extra_form.type'))
                ->columnSpan(1),

            Forms\Components\Toggle::make('is_require')
                ->label(__('sn-product::product.extra_form.is_require'))
                ->helperText(__('sn-product::product.extra_form.is_require_helper'))
                ->columnSpan(1),

            Forms\Components\Toggle::make('is_num')
                ->label(__('sn-product::product.extra_form.is_num'))
                ->helperText(__('sn-product::product.extra_form.is_num_helper'))
                ->live()
                ->columnSpan(1),

            FormComponents::enumsToggleButtons(ExtraStatus::class)
                ->columnSpan(1),

            FormComponents::orderColumnInput()
                ->columnSpan(1),
        ];
    }

    /**
     * 选项行字段（价格 / 永久上下架 / 售罄至 / 数量上下限）。
     */
    public static function optionFields(): array
    {
        return [
            Forms\Components\Repeater::make('children')
                ->relationship('children')
                ->hiddenLabel()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('sn-product::product.extra_form.option_name'))
                        ->required()
                        ->columnSpan(['lg' => 2]),

                    Forms\Components\TextInput::make('price')
                        ->label(__('sn-product::product.extra_form.option_price'))
                        ->numeric()
                        ->prefix('￥')
                        ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/'])
                        ->columnSpan(['lg' => 1]),

                    Forms\Components\Select::make('status')
                        ->label(__('sn-product::product.extra_form.option_status'))
                        ->options(ExtraStatus::class)
                        ->default(ExtraStatus::Up)
                        ->columnSpan(['lg' => 1]),

                    Forms\Components\DateTimePicker::make('sold_out_until')
                        ->label(__('sn-product::product.extra_form.sold_out_until'))
                        ->helperText(__('sn-product::product.extra_form.sold_out_until_helper'))
                        ->seconds()
                        ->minDate(fn () => now())
                        ->suffixAction(
                            Action::make('soldOutToday')
                                ->icon(Heroicon::OutlinedClock)
                                ->tooltip(__('sn-product::product.extra_form.sold_out_today_action'))
                                ->action(fn (Set $set) => $set('sold_out_until', now()->endOfDay()->format('Y-m-d H:i:s')))
                        )
                        ->columnSpan(['lg' => 2]),

                    Schemas\Components\Group::make()
                        ->schema([
                            Forms\Components\TextInput::make('options.limit_min_num')
                                ->label(__('sn-product::product.extra_form.limit_min_num'))
                                ->integer()
                                ->minValue(1)
                                ->columnSpan(1),
                            Forms\Components\TextInput::make('options.limit_max_num')
                                ->label(__('sn-product::product.extra_form.limit_max_num'))
                                ->integer()
                                ->minValue(0)
                                ->helperText(__('sn-product::product.extra_form.limit_max_num_helper'))
                                ->columnSpan(1),
                        ])
                        ->visible(fn (Get $get): bool => (bool) $get('../../is_num'))
                        ->columns(2)
                        ->columnSpan(['lg' => 2]),
                ])
                ->cloneable()
                ->addActionLabel(__('sn-product::product.extra_form.add_option'))
                ->orderColumn('order_column')
                ->defaultItems(0)
                ->minItems(1)
                ->maxItems(30)
                ->columns(['lg' => 6])
                ->columnSpanFull(),
        ];
    }
}
