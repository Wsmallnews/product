<?php

namespace Wsmallnews\Product\Models;

use Wsmallnews\Product\Enums\AttributeStatus;
use Wsmallnews\Support\Casts\MoneyCast;
use Wsmallnews\Support\Models\SupportModel;

class AttributeRepository extends SupportModel
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
