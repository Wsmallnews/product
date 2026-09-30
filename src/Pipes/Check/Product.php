<?php

namespace Wsmallnews\Product\Pipes\Check;

use Closure;
use Wsmallnews\Order\Contracts\Pipes\CheckPipeInterface;
use Wsmallnews\Order\Exceptions\OrderCreateException;
use Wsmallnews\Order\OrderRocket;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Enums\ProductStockType;

/**
 * 商品校验管道：可购性检查（最少购买数量 / 库存）。
 *
 * 多单位商品（spec_type=unit）按 stock_convert_num 把购买数量换算为基础库存数量
 * 后再做库存比较（1 箱 = 12 瓶，扣库存按瓶）。
 */
class Product implements CheckPipeInterface
{
    public function check(OrderRocket $rocket, Closure $next): OrderRocket
    {
        $relateItems = $rocket->getRelateItems();

        if (count($relateItems) === 0) {
            throw (new OrderCreateException(__('sn-product::product.pipes.empty_items')))
                ->setRocket($rocket);
        }

        foreach ($relateItems as $key => &$buyInfo) {
            $product = $buyInfo['product'];
            $currentVariant = $buyInfo['current_variant'];

            // 最少购买一件
            $buyInfo['relate_num'] = max(1, (int) ($buyInfo['product_num'] ?? 1));

            // 多单位商品换算基础库存数量（1 箱 = convert_num 瓶）
            $convertNum = max(1, (int) ($currentVariant->stock_convert_num ?: 1));
            $buyInfo['relate_stock_num'] = $product->spec_type === ProductSpecType::Unit
                ? $buyInfo['relate_num'] * $convertNum
                : $buyInfo['relate_num'];

            // 库存型商品：库存小于购买数量（按基础库存数量口径）
            if ($product->stock_type === ProductStockType::Stock && $currentVariant->stock < $buyInfo['relate_stock_num']) {
                throw (new OrderCreateException(__('sn-product::product.pipes.stock_not_enough', ['title' => $product->title])))
                    ->setRocket($rocket);
            }
        }

        $rocket->setRelateItems($relateItems);

        return $next($rocket);
    }
}
