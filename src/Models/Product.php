<?php

namespace Wsmallnews\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Wsmallnews\Product\Enums;
use Wsmallnews\Support\Casts\MoneyCast;

class Product extends Model implements HasMedia
{
    use HasFactory, SoftDeletes;
    use InteractsWithMedia;

    protected $table = 'sn_products';

    protected $casts = [
        'sku_type' => Enums\ProductSkuType::class,
        'original_price' => MoneyCast::class,
        'price' => MoneyCast::class,
        'status' => Enums\ProductStatus::class,
        'images' => 'array',
        'params' => 'array',
        'options' => 'array',
    ];


    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300)
            ->keepOriginalImageFormat()     // 保持原始格式
            ->nonQueued();
    }


    public function registerMediaCollections(): void
    {
        // 不知道有啥用
        // $this->addMediaCollection('my-collection');
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


    public function skus()
    {
        return $this->hasMany(Sku::class, 'product_id', 'id')->where('parent_id', 0);
    }


    public function skuPrices()
    {
        return $this->hasMany(SkuPrice::class, 'product_id');
    }


    public function skuPrice()
    {
        return $this->skuPrices()->one()->oldestOfMany();
    }
}
