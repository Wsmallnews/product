<?php

return [

    'global_default' => [
        'navigation_group' => '产品管理',
    ],

    'product_status' => [
        'up' => '上架中',
        'down' => '下架',
        'hidden' => '隐藏',
        'draft' => '草稿',
    ],

    'variant_status' => [
        'up' => '上架中',
        'down' => '下架',
    ],

    'attribute_status' => [
        'up' => '上架',
        'down' => '下架',
    ],

    'product_resource' => [
        'model_label' => '产品',
        'plural_model_label' => '产品',
        'navigation_label' => '产品管理',
        'table' => [
            'status' => '状态',
            'search_placeholder' => '搜索产品标题',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ],
        'filter' => [
            'status' => '状态',
            'spec_type' => '规格类型',
        ],
        'form' => [
            'order_column' => '排序',
        ],
    ],

];
