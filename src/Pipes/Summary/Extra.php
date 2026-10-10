<?php

namespace Wsmallnews\Product\Pipes\Summary;

use Closure;
use Wsmallnews\Order\Contracts\Pipes\SummaryPipeInterface;
use Wsmallnews\Order\OrderRocket;

/**
 * 附加项汇总管道：展示文本并入 relate_attributes，价格与选择结构快照进 relate_options。
 *
 * 须排在 Summary/Product 之后（属性集合在其规格文本基础上合并）。
 */
class Extra implements SummaryPipeInterface
{
    public function summary(OrderRocket $rocket, Closure $next): OrderRocket
    {
        $relateItems = $rocket->getRelateItems();

        foreach ($relateItems as $key => &$buyInfo) {
            if (empty($buyInfo['buy_extras'])) {
                continue;
            }

            // 展示文本（珍珠×2）并入属性集合
            $buyInfo['relate_attributes'] = array_merge(
                (array) ($buyInfo['relate_attributes'] ?? []),
                (array) ($buyInfo['extra_texts'] ?? []),
            );

            // relate 附加字段快照（价格整数分；buy_extras 供履约/退款明细）
            $buyInfo['relate_options'] = array_merge(($buyInfo['relate_options'] ?? []), [
                'original_extra_price' => (int) ($buyInfo['original_extra_price'] ?? 0),
                'extra_price' => (int) ($buyInfo['extra_price'] ?? 0),
                'extra_texts' => (array) ($buyInfo['extra_texts'] ?? []),
                'buy_extras' => (array) $buyInfo['buy_extras'],
            ]);
        }

        $rocket->setRelateItems($relateItems);

        return $next($rocket);
    }
}
