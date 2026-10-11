<?php

namespace Wsmallnews\Product\Support;

use Wsmallnews\Product\Models\Product;

/**
 * 商品附加项挂载的表单状态转换（对标 ProductSpecService 范式）。
 *
 * 表单呈现为双层结构（组 → 组内选项），存储是扁平的选项粒度挂载行——
 * toFormState 把挂载行按组聚合回填，save 把双层状态拍平 diff 落库。
 * 软删除的选项：回填时跳过、保存时保留其挂载行（库恢复上架即回归）。
 */
class ExtraItemService
{
    /**
     * 表单状态键（strip 剔除用，主表不落这些字段）。
     */
    public const FORM_STATE_KEYS = ['extra_groups'];

    /**
     * 挂载行 → 双层表单状态（extra_groups: [{group_id, options: [{extra_id, is_default, price_override}]}]）。
     *
     * @return array<string, mixed>
     */
    public static function toFormState(Product $product): array
    {
        $groups = [];

        foreach ($product->extraItems()->orderBy('order_column')->orderBy('id')->get() as $item) {
            $option = $item->extra;
            $group = $option?->parent;

            // 选项不存在（强删）或组不可达（软删/强删）→ 跳过（挂载行保留在库，不呈现）
            if ($option === null || $group === null) {
                continue;
            }

            $groups[$group->id] ??= [
                'group_id' => $group->id,
                'options' => [],
            ];

            $groups[$group->id]['options'][] = [
                'extra_id' => $option->id,
                'is_default' => (bool) $item->is_default,
                'price_override' => $item->price_override !== null ? sn_money()->decimal($item->price_override) : null,
            ];
        }

        return [
            'extra_groups' => array_values($groups),
        ];
    }

    /**
     * 剔除表单状态键（主表字段脱水前）。
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
     * 双层表单状态 → 拍平 diff 落库（新增/更新/删除挂载行）。
     *
     * 组选了但没挑选项的行自然消失；软删除选项的既有挂载行保留（等库恢复）。
     *
     * @param  array<string, mixed>  $data
     */
    public static function save(Product $product, array $data): void
    {
        // 拍平：[{extra_id, is_default, price_override}]（遍历顺序即 order_column）
        $flat = [];
        foreach ((array) ($data['extra_groups'] ?? []) as $group) {
            foreach ((array) ($group['options'] ?? []) as $option) {
                $extraId = (int) ($option['extra_id'] ?? 0);

                if ($extraId === 0) {
                    continue;
                }

                $flat[$extraId] = [
                    'is_default' => (bool) ($option['is_default'] ?? false),
                    'price_override' => filled($option['price_override'] ?? null) ? $option['price_override'] : null,
                ];
            }
        }

        $existing = $product->extraItems()->get();
        $existingByKey = $existing->keyBy('extra_id');

        foreach ($flat as $extraId => $row) {
            $attributes = [
                'is_default' => $row['is_default'],
                'price_override' => $row['price_override'],
                'order_column' => array_search($extraId, array_keys($flat)) + 1,
            ];

            $item = $existingByKey->get($extraId);

            if ($item) {
                $item->update($attributes);
            } else {
                $product->extraItems()->create([
                    'extra_id' => $extraId,
                    ...$attributes,
                ]);
            }
        }

        // 不在表单状态里的挂载行：选项软删除的保留（恢复即回归），其余删除
        $keepIds = array_keys($flat);
        $trashedKeepIds = $existing
            ->filter(fn ($item) => ! in_array($item->extra_id, $keepIds)
                && $item->extra === null)      // 选项已被软删/强删（软删恢复后回归；强删行留之无害）
            ->pluck('extra_id')
            ->all();
        $deleteIds = $existing
            ->pluck('extra_id')
            ->diff(array_merge($keepIds, $trashedKeepIds))
            ->all();

        if ($deleteIds !== []) {
            $product->extraItems()->whereIn('extra_id', $deleteIds)->delete();
        }
    }
}
