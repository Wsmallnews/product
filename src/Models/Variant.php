<?php

namespace Wsmallnews\Product\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Plank\Mediable\MediableInterface;
use Plank\Mediable\Mediable;
use Wsmallnews\Product\Enums;
use Wsmallnews\Support\Casts\ImplodeCast;
use Wsmallnews\Support\Casts\MoneyCast;
use Wsmallnews\Support\Models\SupportModel;

class Variant extends SupportModel implements MediableInterface
{
    use Mediable;

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


    public function mainUrl(): CastAttribute
    {
        return CastAttribute::make(
            get: function (mixed $value, array $attributes): array {
                $firstMedia = $this->firstMedia(['main']);
                $url = [];
                if ($this->relationLoaded('media')) {
                    $url['thumbnail'] = $firstMedia?->findVariant('thumbnail')?->getUrl() ?? null;
                    $url['medium'] = $firstMedia?->findVariant('medium')?->getUrl() ?? null;
                    $url['large'] = $firstMedia?->findVariant('large')?->getUrl() ?? null;
                    $url['original'] = $firstMedia?->getUrl() ?? null;
                }

                return $url;
            }
        );
    }


    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
