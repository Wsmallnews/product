<?php

namespace Wsmallnews\Product\Pipes\Calc;

use Closure;
use Wsmallnews\Order\Contracts\Pipes\CalcPipeInterface;
use Wsmallnews\Order\OrderRocket;
use Wsmallnews\Order\Support\FieldInfo;
use Wsmallnews\Product\Support\ExtraManager;

/**
 * 附加项计价管道（整数分口径）：单份加价 × 商品数量，累计进 relate 单价/小计与订单级 radar。
 *
 * 须排在 Calc/Product 之后（在其单价/小计基础上累加）。
 */
class Extra implements CalcPipeInterface
{
    public function calc(OrderRocket $rocket, Closure $next): OrderRocket
    {
        $relateItems = $rocket->getRelateItems();
        $manager = new ExtraManager;

        foreach ($relateItems as $key => &$buyInfo) {
            if (empty($buyInfo['buy_extras'])) {
                continue;       // 未选附加项，跳过计价
            }

            $num = (int) $buyInfo['relate_num'];

            // 单份加价（分）与展示文本（珍珠×2）
            [$extraPrice, $extraTexts] = $manager->buyCalc($buyInfo['buy_extras']);

            // 总价 = 单份加价 × 商品数量
            $extraAmount = $extraPrice * $num;

            // 订单级累计（分）
            $rocket->radarAdditionMinor('relate_original_amount', $extraAmount);
            $rocket->radarAdditionMinor('relate_amount', $extraAmount);

            // 单价与总价快照（分）
            $buyInfo['original_extra_price'] = $extraPrice;
            $buyInfo['extra_price'] = $extraPrice;
            $buyInfo['original_extra_amount'] = $extraAmount;
            $buyInfo['extra_amount'] = $extraAmount;
            $buyInfo['relate_original_price'] += $extraPrice;
            $buyInfo['relate_price'] += $extraPrice;
            $buyInfo['relate_original_amount'] += $extraAmount;
            $buyInfo['relate_amount'] += $extraAmount;

            // 费用字段集（分，落库口径）与展示字段集（FieldInfo 翻译键）
            $buyInfo['original_amount_fields']['original_extra_amount'] = $extraAmount;
            $buyInfo['amount_fields']['extra_amount'] = $extraAmount;

            $buyInfo['original_amount_fields_info']['original_extra_amount'] = FieldInfo::make(
                fieldName: 'original_extra_amount',
                textKey: 'sn-product::product.order_fields.original_extra_amount',
                value: $extraAmount,
                orderColumn: 2,
            );
            $buyInfo['amount_fields_info']['extra_amount'] = FieldInfo::make(
                fieldName: 'extra_amount',
                textKey: 'sn-product::product.order_fields.extra_amount',
                value: $extraAmount,
                orderColumn: 2,
            );

            $buyInfo['extra_texts'] = $extraTexts;
        }

        $rocket->setRelateItems($relateItems);

        return $next($rocket);
    }
}
