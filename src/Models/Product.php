<?php

namespace Wsmallnews\Product\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wsmallnews\Category\Support\Utils as CategoryUtils;
use Wsmallnews\Preference\Models\Concerns\Preferenceable;
use Wsmallnews\Preference\Models\Concerns\Preferenceable\Viewable;
use Wsmallnews\Product\Enums;
use Wsmallnews\Support\Casts\CounterCast;
use Wsmallnews\Support\Casts\MoneyCast;
use Wsmallnews\Support\Contracts\HasSnSubject;
use Wsmallnews\Support\Models\Concerns\HasActivityLog;
use Wsmallnews\Support\Models\SupportModel;
use Wsmallnews\Support\Support\Utils as SupportUtils;

class Product extends SupportModel implements HasMedia, HasSnSubject
{
    use HasActivityLog;
    use InteractsWithMedia;
    use Preferenceable;
    use SoftDeletes;
    use Viewable;

    protected $table = 'sn_products';

    protected $casts = [
        'counter' => CounterCast::class,
        'sku_type' => Enums\ProductSkuType::class,
        'params' => 'array',
        'price' => MoneyCast::class,
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'options' => 'array',
        'status' => Enums\ProductStatus::class,
    ];

    /**
     * 搜索字段（用于 morphFilter 关键词搜索）。
     */
    public static array $keywordSearchFields = ['title', 'subtitle'];

    protected function getActivityTitleAttribute(): string
    {
        return 'title';
    }

    public function getSnSubjectId(): int
    {
        return $this->id;
    }

    public function getSnSubjectTitle(): string | HtmlString | null
    {
        return $this->title;
    }

    public function getSnSubjectDescription(): string | HtmlString | null
    {
        return $this->subtitle;
    }

    public function getSnSubjectCoverUrl(): string | HtmlString | null
    {
        return $this->getFirstMediaUrl('product_cover');
    }

    public function getSnSubjectHrefUrl(): string | HtmlString | null
    {
        return null;
    }

    /**
     * post 分类多对多查询
     */
    public function scopeCategoryIds($query, array | Collection $categoryIds)
    {
        return $query->whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('id', $categoryIds);
        });
    }

    // public function scopeShow($query)
    // {
    //     return $query->whereIn('status', ['up', 'hidden']);
    // }

    // public function scopeUp($query)
    // {
    //     return $query->where('status', 'up');
    // }

    // public function scopeDown($query)
    // {
    //     return $query->where('status', 'down');
    // }

    // public function scopeHidden($query)
    // {
    //     return $query->where('status', 'hidden');
    // }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CategoryUtils::getCategoryModel(), 'sn_category_product');
    }

    public function content(): MorphOne
    {
        return $this->morphOne(SupportUtils::getContentModel(), 'contentable');
    }

    public function publisher(): MorphTo
    {
        return $this->morphTo();
    }

    // public function stockUnit(): BelongsTo
    // {
    //     return $this->belongsTo(UnitRepository::class, 'stock_unit', 'name');
    // }


    // public function skus(): HasMany
    // {
    //     return $this->hasMany(Sku::class, 'product_id', 'id')->where('parent_id', 0)->orderBy('order_column', 'desc')->orderBy('id', 'asc');
    // }

    // public function attributes(): HasMany
    // {
    //     return $this->hasMany(Attribute::class, 'product_id')->where('attribute_parent_id', 0)->orderBy('order_column', 'desc')->orderBy('id', 'asc');
    // }

    // public function variants(): HasMany
    // {
    //     return $this->hasMany(Variant::class, 'product_id');
    // }


    // public function variant(): HasOne
    // {
    //     return $this->variants()->one()->oldestOfMany();
    // }

    // public function allSkus(): HasMany
    // {
    //     return $this->hasMany(Sku::class, 'product_id', 'id')->orderBy('order_column', 'desc')->orderBy('id', 'asc');
    // }

    public function team(): BelongsTo
    {
        return $this->belongsTo(SupportUtils::getTenantModel());
    }
}
