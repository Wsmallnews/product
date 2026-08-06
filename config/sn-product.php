<?php

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
