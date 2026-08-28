<?php

namespace Wsmallnews\Product\Filament\Resources\Products\Schemas;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Component;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Enums\ProductStockType;
use Wsmallnews\Product\Models\Spec;
use Wsmallnews\Support\Filament\Forms\FormComponents;

/**
 * 产品规格共享表单编辑器。
 *
 * 四种规格类型（Single / Multiple / MainMultiple / Unit）复用同一套组件：
 * - specTypeField()       规格类型切换（切换到多单位时归一化规格名）
 * - singleSpecFieldset()  单规格：变体属性平铺为一级字段（variant.*）
 * - specsRepeaters()      规格项编辑器（Multiple / Unit 一项通用；MainMultiple 时
 *                         拆为「主规格项」+「附加规格项」，主规格的规格值直接携带
 *                         变体属性，所有含该规格值的组合共享此属性）
 * - variantsRepeater()    规格组合编辑器（笛卡尔积自动生成；MainMultiple 时属性列只读）
 *
 * 实现说明：
 * - 类型判断统一通过 `specTypeOf($livewire)` 从 Livewire data 层读取原始值，
 *   不经 `$get()` 读组件 state —— 嵌套 repeater 场景下组件 state 求值会引发递归；
 *   判断入口使用 isSpecSingle / isSpecUnit 等快捷方法。
 * - table 模式 repeater 的 schema 字段与表列从同一份列定义生成
 *   （specValueColumns / variantColumns），保证顺序与数量严格对齐
 *   （Repeater table 按顺序对应字段与表头，错开即列错位）；
 *   `toggleable` 列可由用户在「列设置」中隐藏，偏好存 session。
 * - 组合重算：规格项发生任何变更（改名 / 增删 / 排序）后，按笛卡尔积重建
 *   variants 列表，并以「排序后的子规格名称组合」为指纹保留用户已填的值
 *   （MainMultiple 的属性以主规格值设置为准，不做旧值保留）。
 */
class ProductSpecForm
{
    /**
     * 规格类型切换后重算规格组合；多单位时归一化规格名。
     */
    public static function specTypeField(): ToggleButtons
    {
        return ToggleButtons::make('spec_type')
            ->label('规格类型')
            ->default(ProductSpecType::Single->value)
            ->inline()
            ->grouped()
            ->options(ProductSpecType::class)
            ->live()
            // 编辑/查看页（record 已存在）禁止切换规格类型；
            // disabled 字段不参与脱水，保存时以数据库中的 spec_type 为准
            ->disabled(fn (?Model $record): bool => $record !== null)
            ->afterStateUpdated(function (Component $livewire, Set $set): void {
                if (static::isSpecUnit($livewire)) {
                    // 多单位：规格名固定为「单位」，切换时归一化已有项（保留 key 与 children）
                    $specs = (array) data_get($livewire, 'data.specs');

                    foreach ($specs as $key => $item) {
                        $specs[$key] = [...(array) $item, 'name' => Spec::UNIT_NAME];
                    }

                    $set('specs', $specs);
                }

                (static::recomputeCallback(0))($livewire, $set);
            })
            ->columnSpanFull();
    }

    /**
     * 单规格：变体属性平铺显示（一级字段），保存时写入唯一一条 variant 记录。
     */
    public static function singleSpecFieldset(): Fieldset
    {
        return Fieldset::make('规格信息')
            ->schema([
                static::priceField('variant.price')->required(),
                static::stockField('variant.stock')
                    ->required()
                    ->visible(fn (Component $livewire): bool => static::isSpecSingle($livewire)
                        && static::resolveEnum(data_get($livewire, 'data.stock_type'), ProductStockType::class) !== ProductStockType::Infinite),
                static::snField('variant.product_sn'),
                static::weightField('variant.weight'),
            ])
            ->columns(2)
            ->visible(fn (Component $livewire): bool => static::isSpecSingle($livewire))
            ->columnSpanFull();
    }

    /**
     * 规格项编辑器集合（Multiple / MainMultiple / Unit 共用）。
     *
     * @return array<int, Repeater>
     */
    public static function specsRepeaters(): array
    {
        return [
            // 通用规格项（Multiple / Unit 为全部规格；MainMultiple 为主规格，限 1 项）
            static::specsRepeater(),

            // 附加规格项（仅主多规格显示）
            static::extraSpecsRepeater(),
        ];
    }

    /**
     * 规格项编辑器。
     *
     * 布局：规格名占半行，规格值 table 独占一行。
     * MainMultiple 下作为「主规格项」，其规格值直接携带变体属性（限 1 项）；
     * Unit 下规格名锁定为「单位」（限 1 项）。
     */
    public static function specsRepeater(): Repeater
    {
        return Repeater::make('specs')
            ->label(fn (Component $livewire): string => static::isSpecMainMultiple($livewire) ? '主规格项' : '规格项')
            ->default([])
            ->schema([
                // 数据库记录 id：保存 diff 的匹配键（新项无 id → 新增记录）
                Hidden::make('id'),
                TextInput::make('name')
                    ->label('规格名')
                    ->placeholder('如：颜色、尺码')
                    ->required()
                    ->live(onBlur: true)
                    ->default(fn (Component $livewire): ?string => static::isSpecUnit($livewire) ? Spec::UNIT_NAME : null)
                    ->formatStateUsing(fn (Component $livewire, ?string $state): ?string => static::isSpecUnit($livewire) ? Spec::UNIT_NAME : $state)
                    ->disabled(fn (Component $livewire): bool => static::isSpecUnit($livewire))
                    ->helperText(fn (Component $livewire): ?string => match (true) {
                        static::isSpecUnit($livewire) => '多单位模式下规格名固定为「单位」',
                        static::isSpecMainMultiple($livewire) => '主规格的规格值上设置的属性，将应用到所有含该规格值的组合',
                        default => null,
                    })
                    ->columnSpan(1),
                static::specValuesRepeater(withAttributes: true)
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->itemLabel(fn (Component $livewire, array $state): string => static::isSpecUnit($livewire)
                ? Spec::UNIT_NAME
                : ($state['name'] ?? '未命名规格'))
            ->addActionLabel(fn (Component $livewire): string => static::isSpecMainMultiple($livewire) ? '设置主规格' : '添加规格项')
            ->maxItems(fn (Component $livewire): ?int => static::isSpecOf($livewire, ProductSpecType::Unit, ProductSpecType::MainMultiple) ? 1 : null)
            ->afterStateUpdated(static::recomputeCallback(0))
            ->columnSpanFull();
    }

    /**
     * 附加规格项编辑器（仅主多规格显示）。
     *
     * 附加规格只用于扩展组合，其规格值不携带变体属性。
     */
    protected static function extraSpecsRepeater(): Repeater
    {
        return Repeater::make('extra_specs')
            ->label('附加规格项')
            ->default([])
            ->schema([
                Hidden::make('id'),
                TextInput::make('name')
                    ->label('规格名')
                    ->placeholder('如：颜色、尺码')
                    ->required()
                    ->live(onBlur: true)
                    ->columnSpan(1),
                static::specValuesRepeater(withAttributes: false)
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->itemLabel(fn (array $state): string => $state['name'] ?? '未命名规格')
            ->addActionLabel('添加附加规格项')
            ->visible(fn (Component $livewire): bool => static::isSpecMainMultiple($livewire))
            ->afterStateUpdated(static::recomputeCallback(0))
            ->columnSpanFull();
    }

    /**
     * 子规格 repeater（table 模式）：名称 + 图片，主规格值额外携带变体属性。
     *
     * 字段与表列由列定义（specValueColumns）动态生成，支持「列设置」隐藏可选列。
     *
     * @param  bool  $withAttributes  规格值是否携带变体属性（主多规格的主规格项）
     */
    protected static function specValuesRepeater(bool $withAttributes): Repeater
    {
        $columns = fn (): array => static::specValueColumns($withAttributes);

        return Repeater::make('children')
            ->label('规格值')
            ->hiddenLabel()
            ->default([])
            ->schema(fn (Component $livewire): array => [
                // 数据库记录 id：保存 diff 的匹配键（新项无 id → 新增记录）
                Hidden::make('id'),
                ...static::columnsToFields(
                    $columns(),
                    $livewire,
                    'sn-product.hidden_spec_value_columns',
                ),
            ])
            ->table(fn (Component $livewire): array => static::columnsToTable(
                $columns(),
                $livewire,
                'sn-product.hidden_spec_value_columns',
            ))
            ->hintActions([
                static::columnsToggleAction($columns, 'sn-product.hidden_spec_value_columns'),
            ])
            ->addActionLabel('添加规格值')
            ->afterStateUpdated(static::recomputeCallback(2));
    }

    /**
     * 规格组合编辑器（Multiple / MainMultiple / Unit 共用，table 模式）。
     *
     * 行由规格项自动生成（不可手动增删排序）；规格列以纯文本展示
     * （TextEntry 不脱水，保存时由 ProductSpecService 从 spec_names 重建）；
     * MainMultiple 时变体属性列只读（属性在主规格的规格值上统一设置）；
     * Unit 时额外提供换算比例列。
     */
    public static function variantsRepeater(): Repeater
    {
        return Repeater::make('variants')
            ->label('规格组合')
            ->default([])
            ->schema(fn (Component $livewire): array => [
                // 数据库记录 id（回填携带，供识别记录身份）
                Hidden::make('id'),
                ...static::columnsToFields(
                    static::variantColumns(),
                    $livewire,
                    'sn-product.hidden_variant_columns',
                    ['readOnly' => static::isSpecMainMultiple($livewire)],
                ),
            ])
            ->table(fn (Component $livewire): array => static::columnsToTable(
                static::variantColumns(),
                $livewire,
                'sn-product.hidden_variant_columns',
                ['readOnly' => static::isSpecMainMultiple($livewire)],
            ))
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->hintActions([
                static::columnsToggleAction(
                    fn (): array => static::variantColumns(),
                    'sn-product.hidden_variant_columns',
                ),
            ])
            ->hint(fn (Component $livewire): string => static::isSpecMainMultiple($livewire)
                ? '组合属性由主规格的规格值统一设置，此处不可修改'
                : '规格组合由上方规格项自动生成')
            ->columnSpanFull();
    }

    // ========================= 列定义 =========================

    /**
     * 规格值表的列定义。
     *
     * @param  bool  $withAttributes  规格值是否携带变体属性（仅主多规格的主规格项）
     * @return array<string, array{
     *     label: string,
     *     width?: string,
     *     toggleable?: bool,
     *     visible?: Closure(Component $livewire): bool,
     *     field: Closure,
     * }>
     */
    protected static function specValueColumns(bool $withAttributes): array
    {
        $withAttributesVisible = fn (Component $livewire): bool => $withAttributes && static::isSpecMainMultiple($livewire);

        return [
            'name' => [
                'label' => '名称',
                'field' => fn (): TextInput => TextInput::make('name')
                    ->label('名称')
                    ->placeholder('如：红色')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(static::recomputeCallback(4)),
            ],
            'image' => [
                'label' => '图片',
                'width' => '6rem',
                'field' => fn (): FileUpload => static::imageUploadField()
                    ->afterStateUpdated(static::recomputeCallback(4)),
            ],
            'price' => [
                'label' => '价格',
                'toggleable' => true,
                'visible' => $withAttributesVisible,
                'field' => fn (): TextInput => static::priceField('price'),
            ],
            'stock' => [
                'label' => '库存',
                'toggleable' => true,
                'visible' => $withAttributesVisible,
                'field' => fn (): TextInput => static::stockField('stock'),
            ],
            'product_sn' => [
                'label' => '货号',
                'toggleable' => true,
                'visible' => $withAttributesVisible,
                'field' => fn (): TextInput => static::snField('product_sn'),
            ],
            'weight' => [
                'label' => '重量(KG)',
                'toggleable' => true,
                'visible' => $withAttributesVisible,
                'field' => fn (): TextInput => static::weightField('weight'),
            ],
        ];
    }

    /**
     * 规格组合表的列定义。
     *
     * @return array<string, array{
     *     label: string,
     *     width?: string,
     *     toggleable?: bool,
     *     visible?: Closure(Component $livewire): bool,
     *     field: Closure(bool $readOnly),
     * }>
     */
    protected static function variantColumns(): array
    {
        return [
            'spec' => [
                'label' => '规格',
                'field' => fn (): TextEntry => TextEntry::make('product_spec_text')
                    ->hiddenLabel(),
            ],
            'image' => [
                'label' => '图片',
                'width' => '6rem',
                'field' => fn (): FileUpload => static::imageUploadField(),
            ],
            'product_sn' => [
                'label' => '货号',
                'toggleable' => true,
                'field' => fn (bool $readOnly): TextInput => static::snField('product_sn')->disabled($readOnly),
            ],
            'price' => [
                'label' => '价格',
                'toggleable' => true,
                'field' => fn (bool $readOnly): TextInput => static::priceField('price')->disabled($readOnly),
            ],
            'stock' => [
                'label' => '库存',
                'toggleable' => true,
                'field' => fn (bool $readOnly): TextInput => static::stockField('stock')->disabled($readOnly),
            ],
            'weight' => [
                'label' => '重量(KG)',
                'toggleable' => true,
                'field' => fn (bool $readOnly): TextInput => static::weightField('weight')->disabled($readOnly),
            ],
            'stock_convert_num' => [
                'label' => '换算比例',
                'toggleable' => true,
                'visible' => fn (Component $livewire): bool => static::isSpecUnit($livewire),
                'field' => fn (bool $readOnly): TextInput => static::convertNumField('stock_convert_num'),
            ],
        ];
    }

    /**
     * 列定义 → schema 字段（跳过条件不满足或被用户隐藏的列）。
     *
     * 被隐藏列的字段不进 schema，其 state 保留在 Livewire data 层，
     * 保存走表单 rawState，已填的值不会丢失。
     *
     * @param  array<string, array<string, mixed>>  $columns
     * @param  array<string, mixed>  $context  传给 visible / field 闭包的上下文（按位置）
     * @return array<int, mixed>
     */
    protected static function columnsToFields(array $columns, Component $livewire, string $sessionKey, array $context = []): array
    {
        $fields = [];

        foreach (static::activeColumns($columns, $livewire, $sessionKey, $context) as $definition) {
            $fields[] = ($definition['field'])(...array_values($context));
        }

        return $fields;
    }

    /**
     * 列定义 → 表列（与 columnsToFields 同源，保证字段与表头对齐）。
     *
     * @param  array<string, array<string, mixed>>  $columns
     * @param  array<string, mixed>  $context
     * @return array<int, TableColumn>
     */
    protected static function columnsToTable(array $columns, Component $livewire, string $sessionKey, array $context = []): array
    {
        $tableColumns = [];

        foreach (static::activeColumns($columns, $livewire, $sessionKey, $context) as $definition) {
            $tableColumn = TableColumn::make($definition['label']);

            if (filled($definition['width'] ?? null)) {
                $tableColumn->width($definition['width']);
            }

            $tableColumns[] = $tableColumn;
        }

        return $tableColumns;
    }

    /**
     * 过滤出当前应显示的列（类型条件 + 用户 session 偏好）。
     *
     * @param  array<string, array<string, mixed>>  $columns
     * @param  array<string, mixed>  $context
     * @return array<string, array<string, mixed>>
     */
    protected static function activeColumns(array $columns, Component $livewire, string $sessionKey, array $context = []): array
    {
        $hidden = (array) session($sessionKey, []);

        return array_filter(
            $columns,
            function (array $definition, string $key) use ($livewire, $context, $hidden): bool {
                $visible = $definition['visible'] ?? null;

                if ($visible instanceof Closure && ! $visible($livewire, ...array_values($context))) {
                    return false;
                }

                return ! (($definition['toggleable'] ?? false) && in_array($key, $hidden, true));
            },
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * 「列设置」Action：勾选要隐藏的可选列，偏好存 session。
     *
     * @param  Closure(): array<string, array<string, mixed>>  $columnsResolver
     */
    protected static function columnsToggleAction(Closure $columnsResolver, string $sessionKey): Action
    {
        $toggleableOptions = function (Component $livewire) use ($columnsResolver): array {
            $options = [];

            foreach ($columnsResolver() as $key => $definition) {
                if (! ($definition['toggleable'] ?? false)) {
                    continue;
                }

                $visible = $definition['visible'] ?? null;

                if ($visible instanceof Closure && ! $visible($livewire)) {
                    continue;
                }

                $options[$key] = $definition['label'];
            }

            return $options;
        };

        return Action::make('toggleColumns')
            ->label('列设置')
            ->iconButton()
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->color('gray')
            ->tooltip('选择要显示的列')
            ->visible(fn (Component $livewire): bool => filled($toggleableOptions($livewire)))
            ->schema([
                CheckboxList::make('hidden')
                    ->label('隐藏列')
                    ->helperText('勾选的列将不再显示，已填写的值会保留')
                    ->options(fn (Component $livewire): array => $toggleableOptions($livewire))
                    ->default(session($sessionKey, []))
                    ->bulkToggleable()
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data) use ($sessionKey): void {
                session([$sessionKey => array_values((array) ($data['hidden'] ?? []))]);
            });
    }

    // ========================= 重算逻辑 =========================

    /**
     * 当前是否使用规格编辑器（多规格 / 主多规格 / 多单位）。
     */
    public static function hasSpecs(Component $livewire): bool
    {
        return static::isSpecOf($livewire, ProductSpecType::Multiple, ProductSpecType::MainMultiple, ProductSpecType::Unit);
    }

    /**
     * 规格项变更后，重建规格组合列表（笛卡尔积），保留用户已填的值。
     *
     * 组合指纹优先使用「子规格 id 序列」（编辑态，id 不随改名 / 重排变化，
     * 老组合的值与记录 id 原样保留），创建态（无 id）退化为名称序列；
     * 指纹不依赖 repeater item key（repeater 在 hydrate/dehydrate 时会重写 key）。
     * 主多规格时，组合的变体属性取自主规格（第一组）规格值上的设置。
     *
     * @param  array<string, mixed>  $specsState  规格项 state（specs + extra_specs 已合并，主规格在前）
     * @param  array<string, mixed>  $currentVariants  当前 variants repeater state
     * @return array<string, mixed> 新的 variants repeater state
     */
    public static function computeVariants(array $specsState, array $currentVariants, mixed $specType = null): array
    {
        $isMainMultiple = static::isSpecType($specType, ProductSpecType::MainMultiple);

        // 收集有效的规格组（至少包含一个名称非空、组内去重的规格值）
        $groups = [];

        foreach ($specsState as $spec) {
            $children = collect($spec['children'] ?? [])
                ->filter(fn (array $child): bool => filled($child['name'] ?? null))
                ->unique(fn (array $child): string => (string) $child['name']);

            if ($children->isNotEmpty()) {
                $groups[] = $children;
            }
        }

        // 笛卡尔积：每个组合 = 各规格组中取一个规格值；
        // 主多规格时记录主规格（第一组）规格值作为组合属性来源
        $combos = $groups ? [[]] : [];

        foreach ($groups as $groupIndex => $children) {
            $next = [];

            foreach ($combos as $combo) {
                foreach ($children as $child) {
                    $next[] = [...$combo, [
                        'id' => filled($child['id'] ?? null) ? (int) $child['id'] : null,
                        'name' => (string) $child['name'],
                        'source' => ($isMainMultiple && $groupIndex === 0) ? $child : null,
                    ]];
                }
            }

            $combos = $next;
        }

        // 以 id 优先的组合指纹索引当前组合，保留已填的值与记录 id
        $existing = collect($currentVariants)->keyBy(fn (array $item): string => static::variantKey(
            (array) ($item['spec_ids'] ?? []),
            (array) ($item['spec_names'] ?? []),
        ));

        $variants = [];

        foreach ($combos as $combo) {
            $ids = array_map(fn (array $item): ?int => $item['id'], $combo);
            $names = array_map(fn (array $item): string => $item['name'], $combo);
            $key = static::variantKey($ids, $names);

            // 主多规格：属性来源 = 主规格值；其他类型：保留旧值
            $source = null;
            foreach ($combo as $part) {
                if ($part['source'] !== null) {
                    $source = $part['source'];

                    break;
                }
            }

            $old = $isMainMultiple ? [] : (array) $existing->get($key, []);
            $from = $isMainMultiple ? (array) $source : $old;

            $variants[(string) Str::uuid()] = [
                'id' => $old['id'] ?? null,
                'spec_ids' => $ids,
                'spec_names' => $names,
                'product_spec_text' => implode(',', $names),
                'image' => $from['image'] ?? null,
                'product_sn' => $from['product_sn'] ?? null,
                'price' => $from['price'] ?? null,
                'stock' => $from['stock'] ?? 0,
                'weight' => $from['weight'] ?? 0,
                'stock_convert_num' => $from['stock_convert_num'] ?? 1,
            ];
        }

        return $variants;
    }

    /**
     * 组合指纹：排序后的子规格 id（优先）或名称兜底。
     *
     * @param  array<int, int|null>  $ids
     * @param  array<int, string>  $names
     */
    public static function variantKey(array $ids, array $names): string
    {
        $parts = [];

        foreach ($names as $index => $name) {
            $id = $ids[$index] ?? null;
            $parts[] = filled($id) ? "i:{$id}" : 'n:' . trim((string) $name);
        }

        sort($parts);

        return implode('|', $parts);
    }

    /**
     * 组合指纹（名称版，创建态兜底）。
     *
     * @param  array<int, string>  $names
     */
    public static function variantFingerprint(array $names): string
    {
        return implode('|', collect($names)->map(fn (string $name): string => trim($name))->sort()->values()->all());
    }

    // ========================= 内部工具 =========================

    /**
     * 构建组合重算回调。
     *
     * @param  int  $levels  当前组件相对表单根的层级数（每个 `../` 剥一层 state path）：
     *                       0 = 根级组件；2 = repeater item 内组件；4 = 嵌套 repeater item 内组件
     */
    protected static function recomputeCallback(int $levels): Closure
    {
        $prefix = str_repeat('../', $levels);

        return function (Component $livewire, Set $set) use ($prefix): void {
            // 重入保护：Livewire update 管线中，读组件 data 会再次触发
            // updatedInteractsWithSchemas 形成循环，重入时直接放弃本次重算
            if (static::$isResolvingState) {
                return;
            }

            static::$isResolvingState = true;

            try {
                $set(
                    $prefix . 'variants',
                    static::computeVariants(
                        array_merge(
                            (array) data_get($livewire, 'data.specs'),
                            (array) data_get($livewire, 'data.extra_specs'),
                        ),
                        (array) data_get($livewire, 'data.variants'),
                        static::specTypeOf($livewire),
                    ),
                );
            } finally {
                static::$isResolvingState = false;
            }
        };
    }

    /**
     * 读取当前表单的规格类型原始值（Livewire data 层）。
     *
     * 带重入保护：在 Livewire update 管线中段读取组件 data 会再次触发
     * updatedInteractsWithSchemas 形成无限循环；重入时返回 null（visible
     * 求值为 false），最终渲染阶段的重求值会得到正确结果。
     */
    protected static function specTypeOf(Component $livewire): mixed
    {
        if (static::$isResolvingState) {
            return null;
        }

        static::$isResolvingState = true;

        try {
            return data_get($livewire, 'data.spec_type');
        } finally {
            static::$isResolvingState = false;
        }
    }

    /**
     * 状态读取重入保护标记。
     */
    protected static bool $isResolvingState = false;

    /**
     * 当前表单的规格类型是否命中指定集合（从 Livewire data 层读取）。
     */
    protected static function isSpecOf(Component $livewire, ProductSpecType ...$types): bool
    {
        return static::isSpecType(static::specTypeOf($livewire), ...$types);
    }

    public static function isSpecSingle(Component $livewire): bool
    {
        return static::isSpecOf($livewire, ProductSpecType::Single);
    }

    public static function isSpecMultiple(Component $livewire): bool
    {
        return static::isSpecOf($livewire, ProductSpecType::Multiple);
    }

    public static function isSpecMainMultiple(Component $livewire): bool
    {
        return static::isSpecOf($livewire, ProductSpecType::MainMultiple);
    }

    public static function isSpecUnit(Component $livewire): bool
    {
        return static::isSpecOf($livewire, ProductSpecType::Unit);
    }

    /**
     * 判断规格类型是否命中（表单 state 可能是枚举实例或字符串值，统一归一化后比较）。
     */
    public static function isSpecType(mixed $value, ProductSpecType ...$types): bool
    {
        $current = static::resolveEnum($value, ProductSpecType::class);

        return $current !== null && in_array($current, $types, true);
    }

    /**
     * 把表单 state 中的枚举值归一化为枚举实例后再比较。
     *
     * Filament 对 enum options 的组件会把 state 转为枚举实例，
     * Livewire data 层则是字符串值，统一收敛到枚举形态，
     * 非法值返回 null 而不是静默比较失败。
     *
     * @template TEnum of \BackedEnum
     *
     * @param  class-string<TEnum>  $enumClass
     * @return TEnum|null
     */
    protected static function resolveEnum(mixed $value, string $enumClass): ?\BackedEnum
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        return blank($value) ? null : $enumClass::tryFrom((string) $value);
    }

    // ========================= 共享字段 =========================

    /**
     * 规格图片上传（正方形预览，样式见 sn-product css 的 .sn-spec-upload）。
     */
    protected static function imageUploadField(): FileUpload
    {
        return FormComponents::plainImageUpload('image')
            ->label('图片')
            ->imagePreviewHeight('4.5rem')
            ->extraAttributes(['class' => 'sn-spec-upload'])
            ->live();
    }

    /**
     * 价格输入（表单中为元，保存时由 ProductSpecService 转换）。
     */
    protected static function priceField(string $name): TextInput
    {
        return TextInput::make($name)
            ->label('价格')
            ->placeholder('0.00')
            ->numeric()
            ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/']);
    }

    protected static function stockField(string $name): TextInput
    {
        return TextInput::make($name)
            ->label('库存')
            ->numeric()
            ->minValue(0)
            ->default(0);
    }

    protected static function snField(string $name): TextInput
    {
        return TextInput::make($name)
            ->label('货号')
            ->maxLength(64);
    }

    protected static function weightField(string $name): TextInput
    {
        return TextInput::make($name)
            ->label('重量(KG)')
            ->numeric()
            ->minValue(0);
    }

    /**
     * 换算比例（多单位）：1 当前单位 = ? 基准库存单位。
     */
    protected static function convertNumField(string $name): TextInput
    {
        return TextInput::make($name)
            ->label('换算比例')
            ->numeric()
            ->minValue(0)
            ->default(1)
            ->helperText(fn (Component $livewire): ?string => static::isSpecUnit($livewire)
                ? '1 该单位 = ? ' . data_get($livewire, 'data.stock_unit')
                : null);
    }
}
