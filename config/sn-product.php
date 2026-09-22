<?php

use Wsmallnews\Product\Enums\FormLayout;
use Wsmallnews\Product\Models;
use Wsmallnews\Support\Enums\ContentType;

return [
    /**
     * Scopeable 实例声明（main 为默认实例，必须存在；差异实例按需在此声明，
     * 并在 panel_register 条目中以 'scopeable' => '实例键' 显式引用）
     */
    'scopeables' => [
        'main' => [
            'scope_type' => 'sn-product',
            'scope_id' => 0,
        ],
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
        'spec' => Models\Spec::class,
        'variant' => Models\Variant::class,
        'attribute' => Models\Attribute::class,
        'attribute_repository' => Models\AttributeRepository::class,
        'unit_repository' => Models\UnitRepository::class,
    ],

    /**
     * Panel register
     *
     * product 是基础扩展包：自身不注册后台资源，由消费模块（如 shop）在自己的
     * panel_register 中注册 ProductResource——资源落消费模块的 scopeable
     * （module_id 注册即归属，后台创建的产品存消费模块 main 实例 scope）
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
        'resources' => [],
        'pages' => [],
    ],

    /**
     * File base directory (only used by filament default upload component (Forms\Components\FileUpload))
     */
    'file_directory' => 'sn/product/',

    /**
     * 内容表单配置（FormComponents::contentTypeGroup）
     * types: 允许的内容类型；default_type: 默认内容类型
     */
    'contents' => [
        'product' => [
            'types' => null,
            'default_type' => ContentType::Richtext,
        ],
    ],
];
