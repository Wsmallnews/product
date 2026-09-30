<?php

namespace Wsmallnews\Product\Pipes\Calc;

use Closure;
use Wsmallnews\Order\Contracts\Pipes\CalcPipeInterface;
use Wsmallnews\Order\OrderRocket;
use Wsmallnews\Order\Support\FieldInfo;

/**
 * 商品计价管道（整数分口径，运算走 sn_money）。
 *
 * 前置：单价/小计金额进入 buyInfo 与订单级 radar 累计；
 * 后置：商品域费用集写入订单级 amount_fields / fields_info（供汇总与落单）。
 */
class Product implements CalcPipeInterface
{
    public function calc(OrderRocket $rocket, Closure $next): OrderRocket
    {
        $relateItems = $rocket->getRelateItems();

        // ============================== 前置：逐项计价 ==============================
        foreach ($relateItems as $key => &$buyInfo) {
            $product = $buyInfo['product'];
            $currentVariant = $buyInfo['current_variant'];
            $num = (int) $buyInfo['relate_num'];

            // 单价（分）：划线原价空回落现价
            $originalPrice = sn_money()->minor($product->original_price ?: $currentVariant->price);
            $price = sn_money()->minor($currentVariant->price);

            // 小计（分）= 单价 × 数量
            $originalProductAmount = $originalPrice * $num;
            $productAmount = $price * $num;

            // 订单级累计（分）
            $rocket->radarAdditionMinor('relate_original_amount', $originalProductAmount);
            $rocket->radarAdditionMinor('relate_amount', $productAmount);

            // 单价与总价快照（分）
            $buyInfo['original_product_price'] = $originalPrice;
            $buyInfo['product_price'] = $price;
            $buyInfo['original_product_amount'] = $originalProductAmount;
            $buyInfo['product_amount'] = $productAmount;
            $buyInfo['relate_original_price'] += $originalPrice;
            $buyInfo['relate_price'] += $price;
            $buyInfo['relate_original_amount'] += $originalProductAmount;
            $buyInfo['relate_amount'] += $productAmount;

            $buyInfo['relate_weight'] = round((float) $currentVariant->weight * $num, 2);    // 总重量 KG（非货币，浮点可接受）
            $buyInfo['relate_sn'] = $currentVariant->product_sn;

            // 费用字段集（分，落库口径）
            $buyInfo['original_amount_fields']['original_product_amount'] = $originalProductAmount;
            $buyInfo['amount_fields']['product_amount'] = $productAmount;

            // 展示字段集（text/desc 存翻译键，渲染侧求值；value 整数分，渲染侧格式化）
            $buyInfo['original_amount_fields_info']['original_product_amount'] = FieldInfo::make(
                fieldName: 'original_product_amount',
                textKey: 'sn-product::product.order_fields.original_product_amount',
                value: $originalProductAmount,
                orderColumn: 1,
            );
            $buyInfo['amount_fields_info']['product_amount'] = FieldInfo::make(
                fieldName: 'product_amount',
                textKey: 'sn-product::product.order_fields.product_amount',
                value: $productAmount,
                orderColumn: 1,
                highLight: true,
                descKey: 'sn-product::product.order_fields.desc_items',
                descParams: ['num' => $num],
            );
        }
        $rocket->setRelateItems($relateItems);

        $response = $next($rocket);

        // ============================== 后置：商品域费用汇入订单级 ==============================
        $totalNum = array_sum(array_column($relateItems, 'relate_num'));

        $rocket->setRadar('original_amount_fields.relate_original_amount', $rocket->radarMinor('relate_original_amount'));
        $rocket->setRadar('amount_fields.relate_amount', $rocket->radarMinor('relate_amount'));

        $rocket->setRadar('original_amount_fields_info.relate_original_amount', FieldInfo::make(
            fieldName: 'relate_original_amount',
            textKey: 'sn-product::product.order_fields.relate_original_amount',
            value: $rocket->radarMinor('relate_original_amount'),
            orderColumn: 1,
        ));
        $rocket->setRadar('amount_fields_info.relate_amount', FieldInfo::make(
            fieldName: 'relate_amount',
            textKey: 'sn-product::product.order_fields.relate_amount',
            value: $rocket->radarMinor('relate_amount'),
            orderColumn: 1,
            highLight: true,
            descKey: 'sn-product::product.order_fields.desc_items',
            descParams: ['num' => $totalNum],
        ));

        return $response;
    }
}
