<?php

namespace Wsmallnews\Product\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wsmallnews\Support\Models\SupportModel;

class Spec extends SupportModel
{
    protected $table = 'sn_product_specs';

    protected $guarded = [];

    public $timestamps = false;

    /**
     * 多单位模式下，父级规格（单位项）的固定名称。
     */
    public const UNIT_NAME = '单位';

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id', 'id')->orderBy('order_column')->orderBy('id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(Variant::class, 'sn_product_spec_variants', 'spec_id', 'variant_id');
    }
}
