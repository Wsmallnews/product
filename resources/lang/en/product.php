<?php

return [

    'global_default' => [
        'navigation_group' => 'Product Management',
    ],

    'product_status' => [
        'up' => 'On Sale',
        'down' => 'Off Sale',
        'hidden' => 'Hidden',
        'draft' => 'Draft',
    ],

    'variant_status' => [
        'up' => 'On Sale',
        'down' => 'Off Sale',
    ],

    'attribute_status' => [
        'up' => 'On Sale',
        'down' => 'Off Sale',
    ],

    'product_resource' => [
        'model_label' => 'Product',
        'plural_model_label' => 'Products',
        'navigation_label' => 'Product Management',
        'table' => [
            'status' => 'Status',
            'search_placeholder' => 'Search product titles',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ],
        'filter' => [
            'status' => 'Status',
            'spec_type' => 'Spec Type',
        ],
        'form' => [
            'order_column' => 'Sort Order',
        ],
    ],

    'components' => [
        'products_empty_heading' => 'No products yet',
        'products_empty_description' => 'Products will appear here once they are on sale.',
        'select_spec_first' => 'Please select a specification first.',
        'current_spec' => 'Current specification',
        'product_num' => 'Quantity',
        'buy_now' => 'Buy Now',
        'product_params' => 'Parameters',
        'product_content' => 'Details',
    ],

    'pipes' => [
        'variant_not_found' => 'Product variant not found or unavailable.',
        'empty_items' => 'Please select products to purchase.',
        'stock_not_enough' => 'Insufficient stock: :title',
    ],

    // Order display fields (keys stored in fields_infos text/desc, resolved via __() at render)
    'order_fields' => [
        'original_product_amount' => 'Original product total',
        'product_amount' => 'Product total',
        'relate_original_amount' => 'Original product total',
        'relate_amount' => 'Product total',
        'desc_items' => ':num item(s) in total',
    ],

];
