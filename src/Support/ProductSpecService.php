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
 * - specs: [uuid => ['name' => '颜色', 'children' => [uuid => ['name' => '红', 'image' => ..., ...variant 属性（主多规格的主规格值）]]]]
 * - extra_specs: 附加规格项（仅主多规格，结构与 specs 相同但规格值不带属性）
 * - variants: [uuid => ['spec_names' => ['红', 'M'], 'product_spec_text' => '红,M', ...variant 属性]]
 * - variant: 单规格平铺字段（variant.price / stock / product_sn / weight）
 */
class ProductSpecService
{
    /**
     * 表单中规格相关、不属于 sn_products 表的 state 键。
     */
    public const FORM_STATE_KEYS = ['specs', 'extra_specs', 'variants', 'variant'];

    /**
     * 保存（全量替换）产品的规格树与变体列表，并同步主表价格。
     *
     * @param  array<string, mixed>  $data  表单 dehydrated state（含规格相关键）
     */
    public static function save(Product $product, array $data): void
    {
        $specType = $product->spec_type instanceof ProductSpecType
            ? $product->spec_type
            : ProductSpecType::from($product->spec_type);

        static::purge($product);

        $specModel = Utils::getSpecModel();
        $variantModel = Utils::getVariantModel();

        $isMainMultiple = $specType === ProductSpecType::MainMultiple;

        // 主多规格：主规格（specs 第一项）的规格值携带属性，组合变体以此为准
        $mainAttributes = [];

        if ($isMainMultiple) {
            foreach ((array) ($data['specs'] ?? []) as $specItem) {
                foreach (($specItem['children'] ?? []) as $child) {
                    if (filled($child['name'] ?? null)) {
                        $mainAttributes[trim((string) $child['name'])] = $child;
                    }
                }

                break; // 仅主规格（第一项）
            }
        }

        // 1. 规格树：父级 = 规格名，子级 = 规格值；nameMap 记录 规格值名称 => 子规格模型
        // 主多规格时 specs = 主规格（在前），extra_specs = 附加规格项
        $allSpecs = array_merge((array) ($data['specs'] ?? []), (array) ($data['extra_specs'] ?? []));

        $nameMap = [];
        $specOrder = 0;

        foreach ($allSpecs as $specItem) {
            $name = $specType === ProductSpecType::Unit ? $specModel::UNIT_NAME : ($specItem['name'] ?? null);
            if (blank($name)) {
                continue;
            }

            $parent = $specModel::create([
                'product_id' => $product->id,
                'parent_id' => 0,
                'name' => $name,
                'order_column' => $specOrder,
            ]);

            $childOrder = 0;

            foreach (($specItem['children'] ?? []) as $child) {
                if (blank($child['name'] ?? null)) {
                    continue;
                }

                $child = $specModel::create([
                    'product_id' => $product->id,
                    'parent_id' => $parent->id,
                    'name' => trim((string) $child['name']),
                    'image' => static::normalizeImage($child['image'] ?? null),
                    'order_column' => $childOrder,
                ]);

                $nameMap[$child->name] = $child;

                $childOrder++;
            }

            $specOrder++;
        }

        // 2. 变体
        if ($specType === ProductSpecType::Single) {
            $flat = $data['variant'] ?? [];

            $variantModel::create([
                'product_id' => $product->id,
                'product_spec_text' => null,
                'product_sn' => $flat['product_sn'] ?? null,
                'image' => null,
                'spec_type' => $specType,
                'price' => static::normalizePrice($flat['price'] ?? null),
                'stock' => (int) ($flat['stock'] ?? 0),
                'weight' => (float) ($flat['weight'] ?? 0),
                'status' => VariantStatus::Up,
            ]);
        } else {
            // 多规格 / 主多规格 / 多单位：按组合指纹生成笛卡尔积变体
            foreach ($data['variants'] ?? [] as $item) {
                $names = array_values(array_filter(array_map('trim', (array) ($item['spec_names'] ?? []))));
                if (empty($names)) {
                    continue;
                }

                $specIds = [];
                $specNames = [];
                foreach ($names as $name) {
                    if (! isset($nameMap[$name])) {
                        continue 2; // 规格值已被删除，跳过无效组合
                    }

                    $specIds[] = $nameMap[$name]->id;
                    $specNames[] = $nameMap[$name]->name;
                }

                // 主多规格：组合属性以主规格值的设置为准（组合首名即主规格值名）
                $source = ($isMainMultiple && isset($mainAttributes[$specNames[0]]))
                    ? $mainAttributes[$specNames[0]]
                    : $item;

                $variant = $variantModel::create([
                    'product_id' => $product->id,
                    // ImplodeCast：数组入库为「红,M」逗号串
                    'product_spec_text' => $specNames,
                    'product_sn' => $source['product_sn'] ?? null,
                    'image' => static::normalizeImage($source['image'] ?? null),
                    'spec_type' => $specType,
                    'price' => static::normalizePrice($source['price'] ?? null),
                    'stock' => (int) ($source['stock'] ?? 0),
                    'weight' => (float) ($source['weight'] ?? 0),
                    'stock_unit' => $specType === ProductSpecType::Unit ? ($specNames[0] ?? null) : null,
                    'stock_convert_num' => $specType === ProductSpecType::Unit ? (int) ($item['stock_convert_num'] ?? 0) : 0,
                    'status' => VariantStatus::Up,
                ]);

                $variant->specs()->attach($specIds);
            }
        }

        // 3. 同步主表价格 = 最低变体价格（MoneyCast 接收元，内部转分）
        $minPrice = (int) $product->variants()->min('price');
        $product->forceFill(['price' => static::toDecimal($minPrice)])->saveQuietly();
    }

    /**
     * 将产品记录转换为表单 state（编辑回填用）。
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

            $target = ($isMainMultiple && $index === 0) ? 'specs' : ($isMainMultiple ? 'extra_specs' : 'specs');

            $state[$target][(string) Str::uuid()] = [
                'name' => $spec->name,
                'children' => $children,
            ];
        }

        // 变体 → variants repeater state
        foreach ($variants as $variant) {
            $item = [
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
     * 清空产品的规格与变体（含关联表）。
     */
    protected static function purge(Product $product): void
    {
        $variantIds = $product->variants()->pluck('id');

        if ($variantIds->isNotEmpty()) {
            DB::table('sn_product_spec_variants')->whereIn('variant_id', $variantIds)->delete();
        }

        $product->variants()->delete();
        $product->specs()->delete();
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
