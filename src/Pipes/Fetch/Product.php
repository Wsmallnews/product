<?php

namespace Wsmallnews\Product\Pipes\Fetch;

use Closure;
use Wsmallnews\Order\Contracts\Pipes\FetchPipeInterface;
use Wsmallnews\Order\Exceptions\OrderCreateException;
use Wsmallnews\Order\OrderRocket;
use Wsmallnews\Product\Enums\VariantStatus;
use Wsmallnews\Product\Models\Product as ProductModel;

/**
 * 商品取数管道：relate_items → 带模型实体的购买信息。
 *
 * 前置：按 scope 查询商品（可购买态 = 上架中；隐藏品可直达可买，列表不可见）
 * 后置：解析 current_variant（购买规格），供 Check/Calc/Summary 消费。
 */
class Product implements FetchPipeInterface
{
    public function fetch(OrderRocket $rocket, Closure $next): OrderRocket
    {
        $scope_type = $rocket->getParam('scope_type', 'default');
        $scope_id = (int) $rocket->getParam('scope_id', 0);

        $relateItems = $rocket->getRelateItems();

        foreach ($relateItems as $key => &$buyInfo) {
            // snScope = scopeable + 租户过滤（多租户开启后按当前租户隔离；单租户环境过滤 team_id 为空的平台商品）
            $product = ProductModel::query()
                ->up()
                ->snScope($scope_type, $scope_id)
                ->with('variants')
                ->findOrFail($buyInfo['product_id'] ?? 0);

            $buyInfo['product'] = $product;
        }
        $rocket->setRelateItems($relateItems);

        $response = $next($rocket);

        // ============================== 后置：全部取数管道完成后解析购买规格 ==============================
        $relateItems = $rocket->getRelateItems();
        foreach ($relateItems as $key => &$buyInfo) {
            $productVariantId = (int) ($buyInfo['product_variant_id'] ?? 0);

            $buyInfo['current_variant'] = $buyInfo['product']->variants
                ->first(fn ($variant) => $variant->id === $productVariantId && $variant->status === VariantStatus::Up);

            if (blank($buyInfo['current_variant'])) {
                throw (new OrderCreateException(__('sn-product::product.pipes.variant_not_found')))
                    ->setRocket($rocket);
            }
        }
        $rocket->setRelateItems($relateItems);

        return $response;
    }
}
