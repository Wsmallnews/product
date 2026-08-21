<?php

namespace Wsmallnews\Product\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Enums\VariantStatus;
use Wsmallnews\Support\Casts\ImplodeCast;
use Wsmallnews\Support\Casts\MoneyCast;
use Wsmallnews\Support\Models\SupportModel;

class Variant extends SupportModel
{
    protected $table = 'sn_product_variants';

    protected $guarded = [];

    protected $casts = [
        'product_spec_text' => ImplodeCast::class,
        'spec_type' => ProductSpecType::class,
        'price' => MoneyCast::class,
        'status' => VariantStatus::class,
    ];

    public function scopeUp($query)
    {
        return $query->where('status', VariantStatus::Up);
    }

    public function scopeDown($query)
    {
        return $query->where('status', VariantStatus::Down);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * 组合中包含的规格值（子规格，parent_id > 0）。
     */
    public function specs(): BelongsToMany
    {
        return $this->belongsToMany(Spec::class, 'sn_product_spec_variants', 'variant_id', 'spec_id');
    }
}
