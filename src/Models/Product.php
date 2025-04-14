<?php

namespace Wsmallnews\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Tags\HasTags;
use Wsmallnews\Product\Product as ProductManager;
use Wsmallnews\Product\Enums;
use Wsmallnews\Support\Casts\MoneyCast;
use Wsmallnews\Support\Models\SupportModel;

class Product extends SupportModel implements HasMedia
{
    use HasFactory;
    use HasTags;
    use SoftDeletes;
    use InteractsWithMedia;

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

    // public function registerMediaConversions(?Media $media = null): void
    // {
    //     $this->addMediaConversion('medium')
    //         ->fit(Fit::Contain, 750, 750)
    //         ->keepOriginalImageFormat()     // 保持原始格式
    //         ->nonQueued();
    // }


    public function registerMediaCollections(): void
    {
        $conversions = [
            'small' => [
                'width' => 300,
                'height' => 300,
            ],
            'medium' => [
                'width' => 500,
                'height' => 500,
            ],
            'large' => [
                'width' => 800,
                'height' => 800,
            ],
        ];

        $mainCollection = $this->addMediaCollection('main');
        $galleryCollection = $this->addMediaCollection('gallery');

        $fallbackUrl = ProductManager::getMediaConfig('fallback.url');
        if ($fallbackUrl) {
            $mainCollection = $mainCollection->useFallbackUrl($fallbackUrl);
            $galleryCollection = $galleryCollection->useFallbackUrl($fallbackUrl);
        }

        $fallbackPath = ProductManager::getMediaConfig('fallback.path');
        if ($fallbackPath) {
            $mainCollection = $mainCollection->useFallbackPath($fallbackPath);
            $galleryCollection = $galleryCollection->useFallbackPath($fallbackPath);
        }

        $mainCollection->registerMediaConversions(function (Media $media) use ($conversions) {
            foreach ($conversions as $key => $conversion) {
                $this->addMediaConversion($key)
                    ->fit(
                        Fit::Contain,
                        $conversion['width'],
                        $conversion['height']
                    )
                    ->keepOriginalImageFormat()     // 保持原始格式
                    ->nonQueued();
            }
        });
    }

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
