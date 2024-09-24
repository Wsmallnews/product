<?php

namespace Wsmallnews\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Wsmallnews\Product\Enums\AttributeStatus;
use Wsmallnews\Support\Casts\MoneyCast;

class AttributeRepository extends Model
{

    protected $table = 'sn_product_attribute_repositories';

    protected $casts = [
        'price' => MoneyCast::class,
        'options' => 'array',
        'status' => AttributeStatus::class,
    ];


    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
