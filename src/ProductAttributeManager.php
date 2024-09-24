<?php

namespace Wsmallnews\Product;

use Wsmallnews\Product\Exceptions\ProductException;

class ProductAttributeManager
{

    /**
     * 检测用户选择的属性是否正确，并获取用户选择的属性的完整信息
     *
     * @param array $productAttributes
     * @param array $buyAttributes
     * @return array
     * @throws ProductException
     */
    public function buyCheckAndGet($productAttributes, $buyAttributes)
    {
        $buyAttributes = array_column($buyAttributes, null, 'id');

        foreach ($productAttributes as $productAttribute) {
            // 用户选的当前属性
            $currentBuyInfoProductAttribute = isset($buyAttributes[$productAttribute['id']]) ? $buyAttributes[$productAttribute['id']] : [];

            $buyInfoChecked = false;
            $buyInfoMultiple = false;
            if ($currentBuyInfoProductAttribute && isset($currentBuyInfoProductAttribute['children'])) {
                // 用户选的当前属性子属性
                $currentBuyInfoChildren = array_column($currentBuyInfoProductAttribute['children'], null, 'id');

                $buyInfoChecked = ($currentBuyInfoProductAttribute && count($currentBuyInfoChildren)) ? true : false;   // 用户选没选这个属性
                $buyInfoMultiple = ($buyInfoChecked && count($currentBuyInfoChildren) > 1) ? true : false;              // 用户是否选了多个
            }

            if ($buyInfoChecked) {
                // 用户选择了
                if (in_array($productAttribute['status'], ['down', 'disabled'])) {
                    throw new ProductException('属性 ' . $productAttribute['name'] . ' 不可选择');
                }
            } else {
                // 用户没选择
                if ($productAttribute['is_require']) {
                    // 当前属性必选,但是用户没选
                    throw new ProductException('属性 ' . $productAttribute['name'] . ' 必须选择');
                }

                continue;
            }

            // 检测多选
            if (!$productAttribute['is_multiple'] && $buyInfoMultiple) {
                // 当前属性可多选, 但是用户没选
                throw new ProductException('属性 ' . $productAttribute['name'] . ' 不可多选');
            }

            // 父属性
            $currentAttribute = [
                'id' => $productAttribute['id'],
                'attribute_id' => $productAttribute['attribute_id'],
                'attribute_parent_id' => $productAttribute['attribute_parent_id'],
                'name' => $productAttribute['name'],
                'children' => []
            ];

            $children = [];
            foreach ($productAttribute['children'] as $attribute) {
                // 用户选择的当前子属性
                $currentBuyInfoChild = isset($currentBuyInfoChildren[$attribute['id']]) ? $currentBuyInfoChildren[$attribute['id']] : [];
                $buyInfoChildChecked = $currentBuyInfoChild ? true : false;   // 用户选没选这个属性

                if (!$buyInfoChildChecked) {
                    // 用户没选
                    continue;
                }

                if (in_array($attribute['status'], ['down', 'disabled'])) {
                    // 用户选择了
                    throw new ProductException('属性值 ' . $productAttribute['name'] . ' 不可选择');
                }

                if ($productAttribute['is_num']) {
                    // 可以加数量的属性,
                    $limit_min_num = (isset($attribute['limit_min_num']) && intval($attribute['limit_min_num']) > 1) ? intval($attribute['limit_min_num']) : 1;
                    $limit_max_num = (isset($attribute['limit_max_num']) && intval($attribute['limit_max_num']) > 1) ? intval($attribute['limit_max_num']) : 1;

                    // 处理购买数量
                    $currentBuyInfoChild['num'] = (isset($currentBuyInfoChild['num']) && intval($currentBuyInfoChild['num']) > 1) ? intval($currentBuyInfoChild['num']) : 1;

                    // 处理最少购买数量
                    if ($limit_min_num > $currentBuyInfoChild['num']) {
                        throw new ProductException('属性值 ' . $productAttribute['name'] . ' 最少加购 ' . $limit_min_num . '件');
                    }

                    // 处理最多购买数量
                    if ($limit_max_num < $currentBuyInfoChild['num']) {
                        throw new ProductException('属性值 ' . $productAttribute['name'] . ' 最多可购买 ' . $limit_max_num . '件');
                    }
                } else {
                    // 单属性，将数量默认为 1
                    $currentBuyInfoChild['num'] = 1;
                }

                $children[] = [
                    'id' => $attribute['id'],
                    'attribute_id' => $attribute['attribute_id'],
                    'attribute_parent_id' => $attribute['attribute_parent_id'],
                    'num' => $currentBuyInfoChild['num'],
                    'price' => $attribute['price'],
                    'name' => $attribute['name'],
                ];
            }
            $currentAttribute['children'] = $children;

            $newProductAttributes[] = $currentAttribute;
        }

        return $newProductAttributes ?? [];
    }



    /**
     * 计算属性的费用 （所有属性全放到一个数组，不区分属性组）
     *
     * @param array $buyAttributes
     * @return array
     */
    public function buyCalc($buyAttributes)
    {
        $current_product_attribute_amount = '0';
        $current_product_attribute_texts = [];
        foreach ($buyAttributes as $attribute) {
            $current_product_attribute_texts = [];
            foreach ($attribute['children'] as $child) {
                $current_child_product_attribute_amount = bcmul((string)$child['price'], (string)$child['num'], 2);

                $current_product_attribute_amount = bcadd($current_product_attribute_amount, $current_child_product_attribute_amount, 2);

                $current_product_attribute_texts[] = $child['name'] . '*' . $child['num'];
            }
        }

        return [$current_product_attribute_amount, $current_product_attribute_texts];
    }



    /**
     * 简化属性信息，并且按照特定的顺序排列(用来比较是否是相同的属性，然后合并购物车)
     *
     * @param array $productAttributes
     * @return array|null
     */
    public function formatAttribute($productAttributes)
    {
        foreach ($productAttributes as $productAttribute) {
            // 将上面拿到的 productAttribute 简化
            $currentAttribute = [
                'id' => intval($productAttribute['id']),
                'attribute_id' => intval($productAttribute['attribute_id']),
                'children' => []
            ];

            $children = [];
            foreach ($productAttribute['children'] as $attribute) {
                $children[] = [
                    'id' => intval($attribute['id']),
                    'attribute_id' => intval($attribute['attribute_id']),
                    'attribute_parent_id' => intval($attribute['attribute_parent_id']),
                    'num' => intval($attribute['num']),
                    'price' => strval($attribute['price']),
                ];
            }

            $currentAttribute['children'] = $children;

            $formatAttributes[] = $currentAttribute;
        }

        return $formatAttributes ?? null;
    }
    
}
