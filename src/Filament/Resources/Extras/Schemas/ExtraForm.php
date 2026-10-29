<?php

namespace Wsmallnews\Product\Filament\Resources\Extras\Schemas;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;
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
                ->schema(fn (Component $livewire): array => static::optionRowFields((bool) data_get($livewire, 'data.is_num')))
                ->table(fn (Component $livewire): array => static::optionTableColumns((bool) data_get($livewire, 'data.is_num')))
                ->cloneable()
                ->addActionLabel(__('sn-product::product.extra_form.add_option'))
                ->orderColumn('order_column')
                ->defaultItems(0)
                ->minItems(1)
                ->maxItems(30)
                ->columnSpanFull(),
        ];
    }

    /**
     * 选项行的 schema 字段（与表列同源，保证顺序与数量对齐）。
     *
     * @return array<int, \Filament\Forms\Components\Field>
     */
    protected static function optionRowFields(bool $withLimits): array
    {
        $fields = [
            Forms\Components\TextInput::make('name')
                ->label(__('sn-product::product.extra_form.option_name'))
                ->required(),

            Forms\Components\TextInput::make('price')
                ->label(__('sn-product::product.extra_form.option_price'))
                ->helperText(__('sn-product::product.extra_form.option_price_helper'))
                ->numeric()
                ->prefix('￥')
                ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/']),

            Forms\Components\Select::make('status')
                ->label(__('sn-product::product.extra_form.option_status'))
                ->options(ExtraStatus::class)
                ->default(ExtraStatus::Up),

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
                ),
        ];

        if ($withLimits) {
            // 数量上下限各占一列；留空即不限（服务端归一：min → 1，max → 不校验）
            $fields[] = Forms\Components\TextInput::make('options.limit_min_num')
                ->label(__('sn-product::product.extra_form.limit_min_num'))
                ->helperText(__('sn-product::product.extra_form.limit_min_num_helper'))
                ->integer()
                ->minValue(1);

            $fields[] = Forms\Components\TextInput::make('options.limit_max_num')
                ->label(__('sn-product::product.extra_form.limit_max_num'))
                ->helperText(__('sn-product::product.extra_form.limit_max_num_helper'))
                ->integer()
                ->minValue(1);
        }

        return $fields;
    }

    /**
     * 选项表的表列（与 schema 字段同源，保证顺序与数量对齐）。
     *
     * @return array<int, \Filament\Forms\Components\Repeater\TableColumn>
     */
    protected static function optionTableColumns(bool $withLimits): array
    {
        $columns = [
            Forms\Components\Repeater\TableColumn::make(__('sn-product::product.extra_form.option_name')),
            FormComponents::hintedTableColumn(
                __('sn-product::product.extra_form.option_price'),
                __('sn-product::product.extra_form.option_price_helper'),
            )->width('8rem'),
            Forms\Components\Repeater\TableColumn::make(__('sn-product::product.extra_form.option_status'))->width('9rem'),
            FormComponents::hintedTableColumn(
                __('sn-product::product.extra_form.sold_out_until'),
                __('sn-product::product.extra_form.sold_out_until_helper'),
            )->width('13rem'),
        ];

        if ($withLimits) {
            $columns[] = FormComponents::hintedTableColumn(
                __('sn-product::product.extra_form.limit_min_num'),
                __('sn-product::product.extra_form.limit_min_num_helper'),
            )->width('9rem');
            $columns[] = FormComponents::hintedTableColumn(
                __('sn-product::product.extra_form.limit_max_num'),
                __('sn-product::product.extra_form.limit_max_num_helper'),
            )->width('9rem');
        }

        return $columns;
    }
}
