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

    'extra_type' => [
        'single' => '单选',
        'multiple' => '多选',
    ],

    'extra_status' => [
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

    'extra_resource' => [
        'model_label' => '附加项',
        'plural_model_label' => '附加项库',
        'navigation_label' => '附加项库',
    ],

    'extra_form' => [
        'name' => '附加项名称',
        'description' => '描述',
        'type' => '选择类型',
        'is_require' => '必选',
        'is_require_helper' => '开启后买家必须选择至少一项',
        'is_num' => '按数量计价',
        'is_num_helper' => '开启后买家可选数量（如小料 ×2）',
        'options_section' => '选项',
        'option_name' => '选项名称',
        'option_price' => '加价（元）',
        'option_status' => '状态',
        'sold_out_until' => '售罄至',
        'sold_out_until_helper' => '该时间点之前置灰不可选，过后自动恢复；不能选择当前时间之前（临时售罄用此项，永久停卖用状态；时钟图标一键售罄至当天结束）',
        'sold_out_today_action' => '一键售罄至当天结束',
        'limit_min_num' => '起购数量',
        'limit_max_num' => '最多数量',
        'limit_max_num_helper' => '0 = 不限',
        'add_option' => '添加选项',
    ],

    'extra_table' => [
        'name' => '名称',
        'description' => '描述',
        'type' => '选择类型',
        'is_require' => '必选',
        'is_num' => '数量计价',
        'children_count' => '选项数',
        'status' => '状态',
        'order_column' => '排序',
        'updated_at' => '更新时间',
        'search_placeholder' => '搜索附加项名称',
    ],

    'product_form' => [
        'extras_section' => '附加项',
        'extra_option' => '附加项选项',
        'extra_group' => '所属附加项',
        'extra_price' => '加价（元）',
        'extra_is_default' => '默认选中',
        'extra_price_override' => '覆盖价（元）',
        'extra_price_override_helper' => '留空跟随库价（库里改价即同步）；填写则此商品固定价格',
        'extra_add' => '添加附加项',



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
        'extra_sold_out' => '已售罄',
        'extras_total' => '附加项合计',
    ],

    'pipes' => [
        'variant_not_found' => '商品规格不存在或已下架',
        'empty_items' => '请选择要购买的商品',
        'stock_not_enough' => '商品库存不足：:title',
        'extra_required' => '请选择:name',
        'extra_sold_out' => ':name 已售罄',
        'extra_multiple_not_allowed' => ':name 只能选择一项',
        'extra_num_min' => ':name 至少购买 :min 份',
        'extra_num_max' => ':name 最多购买 :max 份',
        'extra_not_selectable' => '附加项不可用或已下架',
    ],

    // 订单展示字段集（fields_infos 的 text/desc 存键，渲染侧 __() 求值）
    'order_fields' => [
        'original_product_amount' => '商品原价总价',
        'product_amount' => '商品总价',
        'original_extra_amount' => '附加项原价总价',
        'extra_amount' => '附加项总价',
        'relate_original_amount' => '商品原价总价',
        'relate_amount' => '商品总价',
        'desc_items' => '共 :num 件商品',
    ],

];
