<?php

namespace Wsmallnews\Product\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plank\Mediable\MediableInterface;
use Plank\Mediable\Mediable;
use Spatie\Tags\HasTags;
use Wsmallnews\Product\Enums;
use Wsmallnews\Support\Casts\MoneyCast;
use Wsmallnews\Support\Models\SupportModel;

class Product extends SupportModel implements MediableInterface
{
    use HasFactory;
    use HasTags;
    use SoftDeletes;
    use Mediable;

    protected $table = 'sn_products';

    protected $guarded = [];

    protected $casts = [
        'sku_type' => Enums\ProductSkuType::class,
        'original_price' => MoneyCast::class,
        'price' => MoneyCast::class,
        'status' => Enums\ProductStatus::class,
        'images' => 'array',
        'params' => 'array',
        'options' => 'array',
    ];

    public function scopeShow($query)
    {
        return $query->whereIn('status', ['up', 'hidden']);
    }

    public function scopeUp($query)
    {
        return $query->where('status', 'up');
    }

    public function scopeDown($query)
    {
        return $query->where('status', 'down');
    }

    public function scopeHidden($query)
    {
        return $query->where('status', 'hidden');
    }


    public function stockUnit(): BelongsTo
    {
        return $this->belongsTo(UnitRepository::class, 'stock_unit', 'name');
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


    public function galleryUrls(): CastAttribute
    {
        return CastAttribute::make(
            get: function (mixed $value, array $attributes): array {
                return $this->getMedia(['gallery'])->map(fn($media) => $media->getUrl())->toArray();
            }
        );
    }


    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class, 'product_id', 'id')->where('parent_id', 0)->orderBy('order_column', 'desc')->orderBy('id', 'asc');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class, 'product_id')->where('attribute_parent_id', 0)->orderBy('order_column', 'desc')->orderBy('id', 'asc');
    }

    public function skuPrices(): HasMany
    {
        return $this->hasMany(SkuPrice::class, 'product_id');
    }


    public function skuPrice(): HasOne
    {
        return $this->skuPrices()->one()->oldestOfMany();
    }
}
