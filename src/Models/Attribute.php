<?php

namespace Wsmallnews\Product\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{

    protected $table = 'sn_product_attributes';

    protected $casts = [

    ];


    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
