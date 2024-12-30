<?php

namespace Wsmallnews\Product\Models;

use Wsmallnews\Support\Models\SupportModel;

class Attribute extends SupportModel
{

    protected $table = 'sn_product_attributes';

    protected $casts = [

    ];


    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order_column', 'desc')->orderBy('id', 'asc');
    }


    public function attributeRepository()
    {
        return $this->belongsTo(AttributeRepository::class, 'attribute_id', 'id');
    }
}
