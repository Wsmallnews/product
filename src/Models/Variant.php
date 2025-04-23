<?php

namespace Wsmallnews\Product\Models;

use Wsmallnews\Product\Enums;
use Wsmallnews\Support\Casts\ImplodeCast;
use Wsmallnews\Support\Casts\MoneyCast;
use Wsmallnews\Support\Models\SupportModel;

class Variant extends SupportModel
{

    protected $table = 'sn_product_variants';

    protected $guarded = [];

    protected $casts = [
        'product_sku_ids' => ImplodeCast::class,
        'product_sku_text' => ImplodeCast::class,
        'sku_type' => Enums\ProductSkuType::class,
        'original_price' => MoneyCast::class,
        'cost_price' => MoneyCast::class,
        'price' => MoneyCast::class,
        'status' => Enums\VariantStatus::class,
    ];

    public function scopeUp($query)
    {
        return $query->where('status', 'up');
    }


    public function scopeDown($query)
    {
        return $query->where('status', 'down');
    }
}
