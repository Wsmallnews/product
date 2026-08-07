<?php

use Wsmallnews\Product\Enums\FormLayout;
use Wsmallnews\Product\Filament\Resources\Products\ProductResource;
use Wsmallnews\Product\Models;

return [
    /**
     * Default scopeable
     */
    'scopeable' => [
        'scope_type' => 'sn-product',
        'scope_id' => 0,
    ],

    /**
     * Form layout configuration
     *
     * 'layout'  => Which layout to use: 'plain', 'tabs', 'wizard'
     * 'plain'   => Plain layout options (Section wrapping)
     * 'tabs'    => Tabs layout options
     * 'wizard'  => Wizard layout options
     */
    'form_layout' => [
        'layout' => FormLayout::Wizard->value,

        // Plain layout options
        'plain' => [
            'collapsible' => true,  // Section 是否可折叠
        ],

        // Tabs layout options
        'tabs' => [
            'scrollable' => false,        // 是否可滚动 (Disabling scrollable tabs)
            'vertical' => false,          // 是否垂直布局 (Using vertical tabs)
            'active_tab' => 1,            // 默认激活的 tab
            'persist_tab' => 'tab',       // URL 中持久化 tab 参数，设为 null 禁用
        ],

        // Wizard layout options
        'wizard' => [
            'skippable' => true,          // 是否允许跳过步骤
            'start_on_step' => 1,         // 起始步骤
            'persist_step' => 'step',     // URL 中持久化 step 参数，设为 null 禁用
            'hidden_header' => false,     // 是否隐藏头部
        ],
    ],

    /**
     * Custom models
     */
    'models' => [
        'product' => Models\Product::class,
        'sku' => Models\Sku::class,
        'variant' => Models\Variant::class,
        'attribute' => Models\Attribute::class,
        'attribute_repository' => Models\AttributeRepository::class,
        'unit_repository' => Models\UnitRepository::class,
    ],

    /**
     * Panel register
     *
     * global_default 共享默认（非 FQCN 的 string key）会合并到所有条目：
     *   - navigation_group: 所有页面/资源的默认导航组
     *
     * 条目格式：
     *   - 简单 FQCN：ClassName::class（仅合并共享默认）
     *   - 键值对：ClassName::class => ['key' => 'value']（合并共享默认 + 自定义覆盖）
     *   - 配置项键名使用 snake_case（如 navigation_label、navigation_icon）
     */
    'panel_register' => [
        'global_default' => [
            'navigation_group' => '产品管理',
        ],
        'resources' => [
            ProductResource::class,
        ],
        'pages' => [],
    ],

    /**
     * File base directory (only used by filament default upload component (Forms\Components\FileUpload))
     */
    'file_directory' => 'sn/product/',
];
