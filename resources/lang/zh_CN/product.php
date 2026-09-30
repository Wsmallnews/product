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

    'components' => [
        'products_empty_heading' => '暂无产品',
        'products_empty_description' => '产品上架后将在此展示',
        'select_spec_first' => '请先选择规格',
        'current_spec' => '当前规格',
        'product_num' => '数量',
        'buy_now' => '立即购买',
        'product_params' => '参数信息',
        'product_content' => '图文详情',
    ],

    'pipes' => [
        'variant_not_found' => '商品规格不存在或已下架',
        'empty_items' => '请选择要购买的商品',
        'stock_not_enough' => '商品库存不足：:title',
    ],

    // 订单展示字段集（fields_infos 的 text/desc 存键，渲染侧 __() 求值）
    'order_fields' => [
        'original_product_amount' => '商品原价总价',
        'product_amount' => '商品总价',
        'relate_original_amount' => '商品原价总价',
        'relate_amount' => '商品总价',
        'desc_items' => '共 :num 件商品',
    ],

];
