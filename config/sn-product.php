<?php

// config for Wsmallnews/Order
return [
    'product_resource' => [        // config 存储格式
        'resource' => \Wsmallnews\Product\Resources\ProductResource::class,
        'label' => '产品',
        'plural_label' => '产品管理',
        'slug' => '/products',
        'navigation_item' => true,
        'navigation_group' => null,
        'navigation_icon' => 'heroicon-o-shield-check',
        'navigation_sort' => null,
        // 'default_sort_column'    => 'id',
        // 'default_sort_direction' => 'desc',
        'navigation_count_badge' => false,
    ],


    /*
     * Model name for product record.
     */
    'product_model' => \Wsmallnews\Product\Models\Product::class,
];
