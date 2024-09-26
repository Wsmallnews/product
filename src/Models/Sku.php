<?php

namespace Wsmallnews\Product\Models;

use Illuminate\Database\Eloquent\Model;

class Sku extends Model
{

    protected $table = 'sn_product_skus';
    
    protected $guarded = [];

    protected $casts = [];

    public $timestamps = false;


    public function children()
    {
        return $this->hasMany(Sku::class, 'parent_id');
    }
}
