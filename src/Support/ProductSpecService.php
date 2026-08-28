<?php

namespace Wsmallnews\Product\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Enums\VariantStatus;
use Wsmallnews\Product\Models\Product;

/**
 * 产品规格 / 变体读写服务。
 *
 * 表单 state 契约（与 ProductSpecForm 对应）：
 * - specs: [uuid => ['id' => int|null, 'name' => '颜色', 'children' => [uuid => ['id' => int|null, 'name' => '红', 'image' => ..., ...variant 属性（主多规格的主规格值）]]]]
 * - extra_specs: 附加规格项（仅主多规格，结构与 specs 相同但规格值不带属性）
 * - variants: [uuid => ['id' => int|null, 'spec_names' => ['红', 'M'], 'spec_ids' => [1, 2], 'product_spec_text' => '红,M', ...variant 属性]]
 * - variant: 单规格平铺字段（variant.price / stock / product_sn / weight）
 *
 * 保存采用按数据库 id 的 diff 同步（规则见 tasks/产品模块/规格逻辑优化.md）：
 * 编辑时表单 state 携带记录 id，改名 / 重排 / 改属性均原地更新以保留记录；
 * 仅删除的记录与维度变化后的变体组合会被删除重建。
 */
class ProductSpecService
{
    /**
     * 表单中规格相关、不属于 sn_products 表的 state 键。
     */
    public const FORM_STATE_KEYS = ['specs', 'extra_specs', 'variants', 'variant'];

    /**
     * 保存产品的规格树与变体（按数据库 id 做 diff，尽量保留原记录），并同步主表价格。
     *
     * 匹配规则：
     * - 规格项 / 规格值：state 携带 id 且库中存在 → 原地 UPDATE；无 id → 新增；库中缺失 → 删除
     * - 变体：以其包含的全部子规格 id 集合为指纹匹配；命中 UPDATE，未命中新建，失配删除
     * - 主多规格：组合属性以主规格（specs 第一项）规格值上的设置为准
     *
     * @param  array<string, mixed>  $data  表单 state（含规格相关键）
     */
    public static function save(Product $product, array $data): void
    {
        $specType = $product->spec_type instanceof ProductSpecType
            ? $product->spec_type
            : ProductSpecType::from($product->spec_type);

        DB::transaction(function () use ($product, $data, $specType): void {
            $nameToChild = [];
            $mainAttributes = [];

            static::diffSyncSpecs($product, $data, $specType, $nameToChild, $mainAttributes);
            static::diffSyncVariants($product, $data, $specType, $nameToChild, $mainAttributes);

            // 同步主表价格 = 最低变体价格（MoneyCast 接收元，内部转分）
            $minPrice = (int) $product->variants()->min('price');
            $product->forceFill(['price' => static::toDecimal($minPrice)])->saveQuietly();
        });
    }

    /**
     * 规格树 diff：按 id 三路匹配（更新 / 新增 / 删除）。
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $nameToChild  输出：规格值名称 => 子规格模型（供变体解析）
     * @param  array<string, mixed>  $mainAttributes  输出：主规格值名称 => 规格值 state（主多规格的属性源）
     */
    protected static function diffSyncSpecs(Product $product, array $data, ProductSpecType $specType, array &$nameToChild, array &$mainAttributes): void
    {
        $specModel = Utils::getSpecModel();
        $isMainMultiple = $specType === ProductSpecType::MainMultiple;

        // 目标态：specs + extra_specs 合并（主多规格时主规格在前）
        $allSpecs = array_merge((array) ($data['specs'] ?? []), (array) ($data['extra_specs'] ?? []));

        $existingParents = $specModel::query()
            ->where('product_id', $product->id)
            ->where('parent_id', 0)
            ->get()
            ->keyBy('id');

        $existingChildren = $specModel::query()
            ->where('product_id', $product->id)
            ->where('parent_id', '>', 0)
            ->get()
            ->keyBy('id');

        $keptParentIds = [];
        $keptChildIds = [];

        // 使用独立计数器：specs state 的 key 为 repeater uuid（字符串），
        // 不能作为 order_column / 主规格（第一项）判定依据
        $specIndex = 0;

        foreach ($allSpecs as $specItem) {
            $name = $specType === ProductSpecType::Unit ? $specModel::UNIT_NAME : ($specItem['name'] ?? null);
            if (blank($name)) {
                continue;
            }

            $attributes = [
                'name' => $name,
                'order_column' => $specIndex,
            ];

            $specId = (int) ($specItem['id'] ?? 0);

            if ($specId > 0 && $existingParents->has($specId)) {
                $spec = $existingParents[$specId];
                $spec->update($attributes);
            } else {
                $spec = $specModel::create($attributes + [
                    'product_id' => $product->id,
                    'parent_id' => 0,
                ]);
            }

            $keptParentIds[] = $spec->id;

            $childOrder = 0;

            foreach (($specItem['children'] ?? []) as $child) {
                if (blank($child['name'] ?? null)) {
                    continue;
                }

                $childAttributes = [
                    'name' => trim((string) $child['name']),
                    'image' => static::normalizeImage($child['image'] ?? null),
                    'order_column' => $childOrder,
                ];

                $childId = (int) ($child['id'] ?? 0);

                if ($childId > 0 && $existingChildren->has($childId)) {
                    $childModel = $existingChildren[$childId];
                    $childModel->update($childAttributes);
                } else {
                    $childModel = $specModel::create($childAttributes + [
                        'product_id' => $product->id,
                        'parent_id' => $spec->id,
                    ]);
                }

                $keptChildIds[] = $childModel->id;
                $nameToChild[$childModel->name] = $childModel;

                // 主多规格：主规格（第一项）的规格值携带组合属性
                if ($isMainMultiple && $specIndex === 0) {
                    $mainAttributes[$childModel->name] = $child;
                }

                $childOrder++;
            }

            $specIndex++;
        }

        // 库中消失的记录删除（其关联的变体由 diffSyncVariants 按指纹失配删除）
        $existingChildren->keys()->diff($keptChildIds)->each(fn ($id) => $existingChildren[$id]->delete());
        $existingParents->keys()->diff($keptParentIds)->each(fn ($id) => $existingParents[$id]->delete());
    }

    /**
     * 变体 diff：以「子规格 id 集合」为指纹匹配（更新 / 新建 / 删除）。
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $nameToChild  规格值名称 => 子规格模型
     * @param  array<string, mixed>  $mainAttributes  主规格值名称 => 规格值 state（主多规格）
     */
    protected static function diffSyncVariants(Product $product, array $data, ProductSpecType $specType, array $nameToChild, array $mainAttributes): void
    {
        $variantModel = Utils::getVariantModel();
        $isMainMultiple = $specType === ProductSpecType::MainMultiple;

        if ($specType === ProductSpecType::Single) {
            // 单规格：唯一变体原地更新，不删除重建
            $flat = $data['variant'] ?? [];

            $attributes = [
                'product_spec_text' => null,
                'product_sn' => $flat['product_sn'] ?? null,
                'image' => null,
                'price' => static::normalizePrice($flat['price'] ?? null),
                'stock' => (int) ($flat['stock'] ?? 0),
                'weight' => (float) ($flat['weight'] ?? 0),
            ];

            $variant = $product->variants()->first();

            if ($variant) {
                $variant->update($attributes);
            } else {
                $variant = $variantModel::create($attributes + [
                    'product_id' => $product->id,
                    'spec_type' => $specType,
                    'status' => VariantStatus::Up,
                ]);
            }

            // 单规格无规格关联，清掉历史 pivot（可能从其他类型切换而来）
            $variant->specs()->detach();

            // 从其他类型切换残留的多余变体：清理 pivot 后删除
            $product->variants()->whereKeyNot($variant->id)->get()
                ->each(function ($variant): void {
                    $variant->specs()->detach();
                    $variant->delete();
                });

            return;
        }

        // 库中变体按「子规格 id 排序集合」建立指纹索引
        $existingVariants = $product->variants()->with('specs')->get()
            ->keyBy(fn ($variant): string => $variant->specs->pluck('id')->sort()->values()->implode('|'));

        $handled = [];
        $order = 0;

        foreach ($data['variants'] ?? [] as $item) {
            $names = array_values(array_filter(array_map('trim', (array) ($item['spec_names'] ?? []))));
            if (empty($names)) {
                continue;
            }

            $childModels = [];
            foreach ($names as $name) {
                if (! isset($nameToChild[$name])) {
                    continue 2; // 规格值已不存在，跳过无效组合
                }

                $childModels[] = $nameToChild[$name];
            }

            $fingerprint = collect($childModels)->pluck('id')->sort()->values()->implode('|');

            if (in_array($fingerprint, $handled, true)) {
                continue; // 重复组合，仅保留首个
            }

            // 主多规格：组合属性以主规格值的设置为准（组合首名即主规格值名）
            $source = ($isMainMultiple && isset($mainAttributes[$names[0]]))
                ? $mainAttributes[$names[0]]
                : $item;

            $attributes = [
                // ImplodeCast：数组入库为「红,M」逗号串
                'product_spec_text' => $names,
                'product_sn' => $source['product_sn'] ?? null,
                'image' => static::normalizeImage($source['image'] ?? null),
                'price' => static::normalizePrice($source['price'] ?? null),
                'stock' => (int) ($source['stock'] ?? 0),
                'weight' => (float) ($source['weight'] ?? 0),
                'stock_unit' => $specType === ProductSpecType::Unit ? ($names[0] ?? null) : null,
                'stock_convert_num' => $specType === ProductSpecType::Unit ? (int) ($item['stock_convert_num'] ?? 0) : 0,
                'order_column' => $order,
            ];

            if ($existingVariants->has($fingerprint)) {
                $existingVariants[$fingerprint]->update($attributes);
            } else {
                $variant = $variantModel::create($attributes + [
                    'product_id' => $product->id,
                    'spec_type' => $specType,
                    'status' => VariantStatus::Up,
                ]);

                $variant->specs()->attach(collect($childModels)->pluck('id')->all());
            }

            $handled[] = $fingerprint;
            $order++;
        }

        // 库中失配的变体：清理 pivot 后删除（维度变化 / 规格值被删的组合）
        foreach ($existingVariants as $fingerprint => $variant) {
            if (! in_array($fingerprint, $handled, true)) {
                $variant->specs()->detach();
                $variant->delete();
            }
        }
    }

    /**
     * 将产品记录转换为表单 state（编辑回填用）。
     *
     * 所有项携带数据库 id（保存 diff 的匹配键）；排序以 order_column 为准。
     *
     * @return array<string, mixed>
     */
    public static function toFormState(Product $product): array
    {
        $specType = $product->spec_type instanceof ProductSpecType
            ? $product->spec_type
            : ProductSpecType::from($product->spec_type);

        $isMainMultiple = $specType === ProductSpecType::MainMultiple;

        $state = [
            'specs' => [],
            'extra_specs' => [],
            'variants' => [],
            'variant' => [],
        ];

        $variants = $product->variants()->with('specs')->get();

        // 主多规格：构建 主规格值 id => 变体 映射，用于回填规格值上的属性
        $variantBySpecId = [];
        if ($isMainMultiple) {
            foreach ($variants as $variant) {
                foreach ($variant->specs as $spec) {
                    $variantBySpecId[$spec->id] ??= $variant;
                }
            }
        }

        // 规格树 → specs repeater state
        // 主多规格：第一项（主规格）→ specs，其余 → extra_specs
        foreach ($product->parentSpecs()->get() as $index => $spec) {
            $children = [];
            foreach ($spec->children as $child) {
                $childState = [
                    'id' => $child->id,
                    'name' => $child->name,
                    'image' => $child->image,
                ];

                // 主多规格：主规格的规格值回填变体属性
                if ($isMainMultiple && $index === 0 && isset($variantBySpecId[$child->id])) {
                    $variant = $variantBySpecId[$child->id];

                    $childState['product_sn'] = $variant->product_sn;
                    $childState['price'] = static::toDecimal($variant->price);
                    $childState['stock'] = $variant->stock;
                    $childState['weight'] = $variant->weight;
                }

                $children[(string) Str::uuid()] = $childState;
            }

            $target = ($isMainMultiple && $index > 0) ? 'extra_specs' : 'specs';

            $state[$target][(string) Str::uuid()] = [
                'id' => $spec->id,
                'name' => $spec->name,
                'children' => $children,
            ];
        }

        // 变体 → variants repeater state
        foreach ($variants as $variant) {
            $item = [
                'id' => $variant->id,
                'spec_ids' => $variant->specs->pluck('id')->values()->all(),
                'spec_names' => $variant->specs->pluck('name')->values()->all(),
                // ImplodeCast 读出为数组，表单展示还原为「红,M」
                'product_spec_text' => implode(',', (array) $variant->product_spec_text),
                'image' => $variant->image,
                'product_sn' => $variant->product_sn,
                'price' => static::toDecimal($variant->price),
                'stock' => $variant->stock,
                'weight' => $variant->weight,
            ];

            if ($variant->spec_type === ProductSpecType::Unit) {
                $item['stock_convert_num'] = $variant->stock_convert_num;
            }

            $state['variants'][(string) Str::uuid()] = $item;
        }

        // 单规格：唯一变体的属性平铺到 variant.*
        if ($specType === ProductSpecType::Single) {
            $state['variant'] = static::variantToFlat($variants->first());
        }

        return $state;
    }

    /**
     * 剥离表单 state 中规格相关的键（写入 sn_products 主表前调用）。
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function strip(array $data): array
    {
        foreach (static::FORM_STATE_KEYS as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    /**
     * 变体记录 → 平铺的共享属性数组。
     *
     * @return array<string, mixed>
     */
    protected static function variantToFlat(?object $variant): array
    {
        if (! $variant) {
            return [];
        }

        return [
            'product_sn' => $variant->product_sn,
            'price' => static::toDecimal($variant->price),
            'stock' => $variant->stock,
            'weight' => $variant->weight,
        ];
    }

    /**
     * 上传字段归一化：FileUpload 的空 state 为数组形态，写库前转为路径字符串或 null。
     */
    protected static function normalizeImage(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return blank($value) ? null : (string) $value;
    }

    /**
     * 表单价格值 → MoneyCast 可接受的元值（空值兜底 0，避免命中 NOT NULL 约束）。
     */
    protected static function normalizePrice(mixed $value): float
    {
        return blank($value) ? 0.0 : (float) $value;
    }

    /**
     * Money 对象/分 → 元字符串（两位小数）。
     */
    protected static function toDecimal(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_object($value) && method_exists($value, 'formatByDecimal')) {
            return $value->formatByDecimal();
        }

        return number_format((float) $value / 100, 2, '.', '');
    }
}
