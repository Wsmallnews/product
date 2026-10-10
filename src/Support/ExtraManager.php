<?php

namespace Wsmallnews\Product\Support;

use Wsmallnews\Product\Enums\ExtraStatus;
use Wsmallnews\Product\Exceptions\ProductException;
use Wsmallnews\Product\Models\Product;

/**
 * 附加项（加料/增值服务）购买流程服务：挂载解析、下单校验、计价。
 *
 * resolveProductExtras 是唯一解析源——前台 specSchema 与下单管道消费同一结构：
 * 组（Up）+ 挂载选项（Up，售罄保留标记）+ 有效价（price_override ?? 库价）。
 * 服务端校验永远基于此结构，前端本地选择仅是展示态。
 */
class ExtraManager
{
    /**
     * 解析商品的可用附加项树（组 → 挂载选项，含有效价与售罄标记）。
     *
     * @return list<array{
     *     id: int, name: string, type: string, is_require: bool, is_num: bool,
     *     children: list<array{id: int, name: string, price: int, sold_out: bool, is_default: bool, limit_min_num: int, limit_max_num: int}>
     * }>
     */
    public function resolveProductExtras(Product $product): array
    {
        $extraItems = $product->extraItems->filter(fn ($item) => $item->extra && $item->extra->parent);

        if ($extraItems->isEmpty()) {
            return [];
        }

        // 组（Up）→ 选项行集合；组行经 extra.parent 取（挂载为选项粒度）
        $groups = [];
        foreach ($extraItems as $item) {
            $option = $item->extra;
            $group = $option->parent;

            if ($group->status !== ExtraStatus::Up) {
                continue;
            }

            if ($option->status !== ExtraStatus::Up) {
                continue;      // 永久下架的选项不出现（售罄的保留并置灰）
            }

            $groups[$group->id] ??= [
                'id' => (int) $group->id,
                'name' => (string) $group->name,
                'description' => (string) ($group->description ?? ''),
                'type' => $group->type->value,
                'is_require' => (bool) $group->is_require,
                'is_num' => (bool) $group->is_num,
                'children' => [],
            ];

            $groups[$group->id]['children'][] = [
                'id' => (int) $option->id,
                'name' => (string) $option->name,
                'price' => sn_money()->minor($item->price_override ?? $option->price),      // 覆盖价 ?? 库价（MoneyCast → 分）
                'sold_out' => $option->sold_out,
                'is_default' => (bool) $item->is_default,
                'limit_min_num' => $option->limit_min_num,
                'limit_max_num' => $option->limit_max_num,
            ];
        }

        return array_values($groups);
    }

    /**
     * 校验用户购买的附加项（必选/售罄/单多选/数量上下限），返回规整后的购买结构。
     *
     * @param  array<int, array{id: int, name: string, type: string, is_require: bool, is_num: bool, children: list<array{id: int, name: string, price: int, sold_out: bool, is_default: bool, limit_min_num: int, limit_max_num: int}>}>  $productExtras resolveProductExtras 的产物
     * @param  list<array{id: int, children?: list<array{id: int, num?: int}>>|array<string, mixed>>  $buyExtras 用户提交：[{id: 组 id, children: [{id: 选项 id, num}]}]
     * @return list<array{id: int, name: string, children: list<array{id: int, name: string, num: int, price: int}>}>
     */
    public function buyCheckAndGet(array $productExtras, array $buyExtras): array
    {
        $buyGroups = [];
        foreach ((array) $buyExtras as $buyExtra) {
            if (isset($buyExtra['id'])) {
                $buyGroups[(int) $buyExtra['id']] = array_column((array) ($buyExtra['children'] ?? []), null, 'id');
            }
        }

        $checked = [];

        foreach ($productExtras as $productExtra) {
            $groupId = $productExtra['id'];
            $buyChildren = $buyGroups[$groupId] ?? [];
            $selectedCount = count($buyChildren);

            if ($selectedCount === 0) {
                if ($productExtra['is_require']) {
                    throw new ProductException(__('sn-product::product.pipes.extra_required', ['name' => $productExtra['name']]));
                }

                continue;       // 未选且非必选，跳过
            }

            // 单选组不允许选多个
            if ($productExtra['type'] === 'single' && $selectedCount > 1) {
                throw new ProductException(__('sn-product::product.pipes.extra_multiple_not_allowed', ['name' => $productExtra['name']]));
            }

            $children = [];
            foreach ($productExtra['children'] as $option) {
                $buyChild = $buyChildren[$option['id']] ?? null;

                if ($buyChild === null) {
                    continue;
                }

                // 售罄（当日/售罄至日期内）不可选
                if ($option['sold_out']) {
                    throw new ProductException(__('sn-product::product.pipes.extra_sold_out', ['name' => $option['name']]));
                }

                // 数量：数量型校验起购/限购；非数量型固定 1
                $num = $productExtra['is_num'] ? max(1, (int) ($buyChild['num'] ?? 1)) : 1;

                if ($productExtra['is_num']) {
                    if ($num < $option['limit_min_num']) {
                        throw new ProductException(__('sn-product::product.pipes.extra_num_min', [
                            'name' => $option['name'], 'min' => $option['limit_min_num'],
                        ]));
                    }

                    if ($option['limit_max_num'] > 0 && $num > $option['limit_max_num']) {
                        throw new ProductException(__('sn-product::product.pipes.extra_num_max', [
                            'name' => $option['name'], 'max' => $option['limit_max_num'],
                        ]));
                    }
                }

                $children[] = [
                    'id' => $option['id'],
                    'name' => $option['name'],
                    'num' => $num,
                    'price' => $option['price'],
                ];
            }

            // 提交里有该组选项但都不在挂载集合内（未挂/已下架/伪造）
            if ($children === []) {
                if ($productExtra['is_require']) {
                    throw new ProductException(__('sn-product::product.pipes.extra_required', ['name' => $productExtra['name']]));
                }

                continue;
            }

            $checked[] = [
                'id' => $groupId,
                'name' => $productExtra['name'],
                'children' => $children,
            ];
        }

        // 提交了未挂载/已下架的组（伪造或过期数据）
        $knownGroupIds = array_column($productExtras, 'id');
        foreach (array_keys($buyGroups) as $buyGroupId) {
            if (! in_array($buyGroupId, $knownGroupIds, true)) {
                throw new ProductException(__('sn-product::product.pipes.extra_not_selectable'));
            }
        }

        return $checked;
    }

    /**
     * 计算附加项加价（单份商品口径，不乘商品数量——乘数量在管道内完成）。
     *
     * @param  list<array{id: int, name: string, children: list<array{id: int, name: string, num: int, price: int}>}>  $buyExtras buyCheckAndGet 的产物
     * @return array{0: int, 1: list<string>}    [加价合计（分）, 展示文本（珍珠×2）]
     */
    public function buyCalc(array $buyExtras): array
    {
        $amount = 0;
        $texts = [];

        foreach ($buyExtras as $buyExtra) {
            foreach ($buyExtra['children'] as $child) {
                // 整数分 × 整数数量，直接整型乘法（同 Calc/Product 管道口径）
                $amount += $child['price'] * $child['num'];

                $texts[] = $child['num'] > 1
                    ? $child['name'] . '×' . $child['num']
                    : $child['name'];
            }
        }

        return [$amount, $texts];
    }
}
