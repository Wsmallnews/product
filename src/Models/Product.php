<?php

namespace Wsmallnews\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
use Wsmallnews\Product\Database\Factories\ProductFactory;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Support\Casts\CounterCast;
use Wsmallnews\Support\Casts\MoneyCast;
use Wsmallnews\Support\Contracts\HasSnSubject;
use Wsmallnews\Support\Models\Concerns\HasActivityLog;
use Wsmallnews\Support\Models\SupportModel;
use Wsmallnews\Support\Support\Utils as SupportUtils;

class Product extends SupportModel implements HasMedia, HasSnSubject
{
    use HasActivityLog;
    use HasFactory;
    use InteractsWithMedia;
    use Preferenceable;
    use SoftDeletes;
    use Viewable;

    protected $table = 'sn_products';

    protected $guarded = [];

    protected $casts = [
        'counter' => CounterCast::class,
        'spec_type' => ProductSpecType::class,
        'params' => 'array',
        'price' => MoneyCast::class,
        'published_at' => 'datetime',
        'options' => 'array',
        'status' => ProductStatus::class,
    ];

    /**
     * 搜索字段（用于 morphFilter 关键词搜索）。
     */
    public static array $keywordSearchFields = ['title', 'subtitle'];

    protected static function newFactory()
    {
        return ProductFactory::new();
    }

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
        return $this->getFirstMediaUrl('product_image');
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

    /**
     * 定时调度任务关联
     */
    public function scheduledTasks(): MorphMany
    {
        return $this->morphMany(SupportUtils::getScheduledTaskModel(), 'schedulable');
    }

    /**
     * 产品下的全部规格项（含父级规格名与子级规格值，通过 parent_id 区分层级）。
     */
    public function specs(): HasMany
    {
        return $this->hasMany(Spec::class, 'product_id', 'id');
    }

    /**
     * 父级规格（规格名，如"颜色"、"尺码"）。
     */
    public function parentSpecs(): HasMany
    {
        return $this->specs()->where('parent_id', 0)->orderBy('order_column')->orderBy('id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class, 'product_id', 'id')->orderBy('order_column')->orderBy('id');
    }

    /**
     * 默认变体（单规格场景下即唯一的变体）。
     */
    public function variant(): HasOne
    {
        return $this->variants()->one();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(SupportUtils::getTenantModel());
    }
}
