<?php

namespace Wsmallnews\Product\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wsmallnews\Product\Enums\ExtraStatus;
use Wsmallnews\Product\Enums\ExtraType;
use Wsmallnews\Support\Casts\MoneyCast;
use Wsmallnews\Support\Models\SupportModel;

/**
 * 附加项库（加料/增值服务）：两级自关联，软删除。
 *
 * 组行（parent_id=0）持有选择规则（type 单选/多选、is_require 必选、is_num 数量计价）；
 * 选项行持有价格与售罄控制（sold_out_until 时间点之前置灰，过后自动恢复，惰性比较无定时任务）。
 *
 * 删除语义：库记录软删除——商品挂载行保留（关联为空 → 前台/下单自动消失，恢复上架即回归），
 * 订单历史不受影响（快照自包含）；组行删除级联软删其选项行。
 */
class Extra extends SupportModel
{
    use SoftDeletes;

    protected $table = 'sn_product_extras';

    protected $guarded = [];

    protected $casts = [
        'type' => ExtraType::class,
        'status' => ExtraStatus::class,
        'is_require' => 'boolean',
        'is_num' => 'boolean',
        'price' => MoneyCast::class,
        'sold_out_until' => 'datetime',
        'options' => 'array',
    ];

    /**
     * 组行删除/恢复级联子项：软删 → 级联软删；强删 → 级联强删；恢复 → 级联恢复
     * （forceDeleting 事件先于 deleting 触发，强删时子项已被硬删，deleting 钩子无行可操作）。
     */
    protected static function booted(): void
    {
        static::deleting(function (Extra $extra): void {
            if ($extra->parent_id === 0) {
                $extra->children()->delete();
            }
        });

        static::forceDeleting(function (Extra $extra): void {
            if ($extra->parent_id === 0) {
                $extra->children()->forceDelete();
            }
        });

        static::restored(function (Extra $extra): void {
            if ($extra->parent_id === 0) {
                $extra->children()->restore();
            }
        });
    }

    /**
     * 选项当前是否售罄（sold_out_until 时间点及之前 = 售罄；过后自动恢复）。
     */
    protected function soldOut(): Attribute
    {
        return Attribute::get(
            fn () => $this->sold_out_until !== null
                && $this->sold_out_until->isFuture(),
        );
    }

    /**
     * 选项起购数量（数量型生效，默认 1）。
     */
    protected function limitMinNum(): Attribute
    {
        return Attribute::get(
            fn () => max(1, (int) ($this->options['limit_min_num'] ?? 1)),
        );
    }

    /**
     * 选项最多购买数量（数量型生效，0 = 不限）。
     */
    protected function limitMaxNum(): Attribute
    {
        return Attribute::get(
            fn () => max(0, (int) ($this->options['limit_max_num'] ?? 0)),
        );
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id', 'id')->orderBy('order_column')->orderBy('id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExtraItem::class, 'extra_id', 'id');
    }
}
