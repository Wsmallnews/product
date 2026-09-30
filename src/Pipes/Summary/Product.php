<?php

namespace Wsmallnews\Product\Pipes\Summary;

use Closure;
use Wsmallnews\Order\Contracts\Pipes\SummaryPipeInterface;
use Wsmallnews\Order\OrderRocket;

/**
 * 商品汇总管道：购买信息 → 订单快照字段（relate_*）。
 *
 * 快照原则：下单时固化 title/subtitle/image/规格/单价（分）等展示与履约要素，
 * 后续商品改动不影响存量订单。
 */
class Product implements SummaryPipeInterface
{
    public function summary(OrderRocket $rocket, Closure $next): OrderRocket
    {
        $relateItems = $rocket->getRelateItems();

        foreach ($relateItems as $key => &$buyInfo) {
            $product = $buyInfo['product'];
            $currentVariant = $buyInfo['current_variant'];

            // relate 快照
            $buyInfo['relate_type'] = 'product';
            $buyInfo['relate_id'] = $product->id;
            $buyInfo['relate_title'] = $product->title;
            $buyInfo['relate_subtitle'] = $product->subtitle ?? '';
            $buyInfo['relate_image'] = $currentVariant->image ?: $product->getFirstMediaUrl('product_image');

            // 属性集合 = 入参携带的属性（购物车加的小料等）+ 规格值（空值剔除）
            $buyInfo['relate_attributes'] = array_values(array_filter([
                ...(array) ($buyInfo['relate_attributes'] ?? []),
                ...(array) $currentVariant->product_spec_text,
            ], fn ($attribute) => filled($attribute)));

            $buyInfo['stock_unit'] = $product->stock_unit ?? '';
            $buyInfo['stock_type'] = $product->stock_type->value;

            // relate 附加字段（spec 与单价快照，履约/退款/加料计算用；价格整数分）
            $buyInfo['relate_options'] = array_merge(($buyInfo['relate_options'] ?? []), [
                'product_type' => $product->type,
                'product_variant_id' => $currentVariant->id,
                'product_sn' => $currentVariant->product_sn,
                'product_spec_text' => $currentVariant->product_spec_text,
                'original_product_price' => (int) ($buyInfo['original_product_price'] ?? 0),
                'product_price' => (int) ($buyInfo['product_price'] ?? 0),
                'product_spec_type' => $product->spec_type->value,
            ]);
        }

        $rocket->setRelateItems($relateItems);

        return $next($rocket);
    }
}
