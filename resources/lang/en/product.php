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

    'extra_type' => [
        'single' => 'Single Choice',
        'multiple' => 'Multiple Choice',
    ],

    'extra_status' => [
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

    'extra_resource' => [
        'model_label' => 'Extra',
        'plural_model_label' => 'Extras',
        'navigation_label' => 'Extras Library',
    ],

    'extra_form' => [
        'name' => 'Extra Name',
        'description' => 'Description',
        'type' => 'Choice Type',
        'is_require' => 'Required',
        'is_require_helper' => 'Buyers must pick at least one option',
        'is_num' => 'Priced by Quantity',
        'is_num_helper' => 'Buyers can choose a quantity (e.g. topping x2)',
        'options_section' => 'Options',
        'option_name' => 'Option Name',
        'option_price' => 'Extra Price',
        'option_status' => 'Status',
        'sold_out_until' => 'Sold Out Until',
        'sold_out_until_helper' => 'Unavailable before this time, auto-recovers afterwards; past times not allowed',
        'sold_out_today_action' => 'Sold out until end of today',
        'limit_min_num' => 'Min Quantity',
        'limit_max_num' => 'Max Quantity',
        'limit_max_num_helper' => '0 = unlimited',
        'add_option' => 'Add Option',
    ],

    'extra_table' => [
        'name' => 'Name',
        'description' => 'Description',
        'type' => 'Choice Type',
        'is_require' => 'Required',
        'is_num' => 'By Quantity',
        'children_count' => 'Options',
        'status' => 'Status',
        'order_column' => 'Sort Order',
        'updated_at' => 'Updated At',
        'search_placeholder' => 'Search extra names',
    ],

    'product_form' => [
        'extras_section' => 'Extras',
        'extra_option' => 'Extra Option',
        'extra_group' => 'Extra Group',
        'extra_price' => 'Extra Price',
        'extra_is_default' => 'Selected by Default',
        'extra_price_override' => 'Price Override',
        'extra_price_override_helper' => 'Empty = follow library price (library changes sync); set = fixed price for this product',
        'extra_add' => 'Add Extra',



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
        'extra_sold_out' => 'Sold Out',
        'extras_total' => 'Extras Total',
    ],

    'pipes' => [
        'variant_not_found' => 'Product variant not found or unavailable.',
        'empty_items' => 'Please select products to purchase.',
        'stock_not_enough' => 'Insufficient stock: :title',
        'extra_required' => 'Please select :name',
        'extra_sold_out' => ':name is sold out',
        'extra_multiple_not_allowed' => 'Only one option can be selected for :name',
        'extra_num_min' => 'Minimum quantity for :name is :min',
        'extra_num_max' => 'Maximum quantity for :name is :max',
        'extra_not_selectable' => 'Extra is unavailable or off sale',
    ],

    // Order display fields (keys stored in fields_infos text/desc, resolved via __() at render)
    'order_fields' => [
        'original_product_amount' => 'Original product total',
        'product_amount' => 'Product total',
        'original_extra_amount' => 'Original extras total',
        'extra_amount' => 'Extras total',
        'relate_original_amount' => 'Original product total',
        'relate_amount' => 'Product total',
        'desc_items' => ':num item(s) in total',
    ],

];
