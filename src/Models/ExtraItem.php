<?php

namespace Wsmallnews\Product\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wsmallnews\Support\Casts\MoneyCast;
use Wsmallnews\Support\Models\SupportModel;

/**
 * 商品附加项挂载（选项粒度）。
 *
 * 挂载哪个库选项、是否默认选中、按商品覆盖价：
 * price_override 空 = 用库价（库改价全局同步）；有值 = 该商品固定价（订单照常快照落单价）。
 */
class ExtraItem extends SupportModel
{
    protected $table = 'sn_product_extra_items';

    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'price_override' => MoneyCast::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * 挂载的库选项行（组规则经 extra.parent 读取）。
     */
    public function extra(): BelongsTo
    {
        return $this->belongsTo(Extra::class, 'extra_id', 'id');
    }
}
