<?php

namespace Wsmallnews\Product\Pipes\Check;

use Closure;
use Wsmallnews\Order\Contracts\Pipes\CheckPipeInterface;
use Wsmallnews\Order\Exceptions\OrderCreateException;
use Wsmallnews\Order\OrderRocket;
use Wsmallnews\Product\Support\ExtraManager;

/**
 * 附加项校验管道：必选/售罄/单多选/数量上下限（ExtraManager 统一判据）。
 *
 * 前置：buy_extras 规整结果写入 buyInfo，供 Calc/Summary 消费；
 * 校验失败抛 OrderCreateException（携带 rocket），消息走翻译键。
 */
class Extra implements CheckPipeInterface
{
    public function check(OrderRocket $rocket, Closure $next): OrderRocket
    {
        $relateItems = $rocket->getRelateItems();
        $manager = new ExtraManager;

        foreach ($relateItems as $key => &$buyInfo) {
            $buyInfo['extras'] = (array) ($buyInfo['extras'] ?? []);

            try {
                $buyInfo['buy_extras'] = $manager->buyCheckAndGet(
                    $manager->resolveProductExtras($buyInfo['product']),
                    $buyInfo['extras'],
                );
            } catch (\Wsmallnews\Product\Exceptions\ProductException $e) {
                throw (new OrderCreateException($e->getMessage()))->setRocket($rocket);
            }
        }

        $rocket->setRelateItems($relateItems);

        return $next($rocket);
    }
}
