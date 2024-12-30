<?php

namespace Wsmallnews\Product\Models;

use Wsmallnews\Support\Models\SupportModel;

class Sku extends SupportModel
{

    protected $table = 'sn_product_skus';

    protected $guarded = [];

    protected $casts = [];

    public $timestamps = false;


    public function children()
    {
        return $this->hasMany(Sku::class, 'parent_id')->orderBy('order_column', 'desc')->orderBy('id', 'asc');
    }
}
