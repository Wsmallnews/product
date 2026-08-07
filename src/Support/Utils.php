<?php

declare(strict_types=1);

namespace Wsmallnews\Product\Support;

use Wsmallnews\Product\Exceptions\ProductException;
use Wsmallnews\Support\Data\ScopeableContext;
use Wsmallnews\Support\Exceptions\InvalidScopeException;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Utility class for Product package configuration and helpers.
 */
class Utils
{
    /**
     * Get configuration value.
     *
     * @param  string|null  $name  Configuration key (dot notation)
     * @param  mixed  $default  Default value if not found
     */
    public static function getConfig(?string $name = null, mixed $default = null): mixed
    {
        $config = config('sn-product');

        return $name ? (data_get($config, $name) ?? $default) : $config;
    }

    /**
     * Get scopeable configuration as ScopeableContext object.
     *
     * @throws ProductException
     */
    public static function getScopeableContext(): ScopeableContext
    {
        try {
            return SupportUtils::getScopeFromConfig('sn-product.scopeable');
        } catch (InvalidScopeException $e) {
            throw new ProductException('Scopeable configuration error. ' . $e->getMessage());
        }
    }

    /**
     * Get scopeable array (legacy method for backward compatibility).
     *
     * @return array{scope_type: string, scope_id: int}
     *
     * @throws ProductException
     */
    public static function getScopeable(): array
    {
        return self::getScopeableContext()->toArray();
    }

    /**
     * Get scope type.
     *
     * @throws ProductException
     */
    public static function getScopeType(): string
    {
        return self::getScopeableContext()->scopeType;
    }

    /**
     * Get scope ID.
     *
     * @throws ProductException
     */
    public static function getScopeId(): int
    {
        return self::getScopeableContext()->scopeId;
    }

    /**
     * Get panel register raw config.
     *
     * @param  string  $type  Register type (pages or resources)
     */
    public static function getPanelRegister(?string $type = 'pages'): mixed
    {
        if (blank($type)) {
            return self::getConfig('panel_register', null);
        }

        return self::getConfig("panel_register.$type", null);
    }

    /**
     * 获取表单布局配置
     */
    public static function getFormLayout(?string $name = null): array
    {
        if (blank($name)) {
            return static::getConfig('form_layout', []);
        }

        return static::getConfig("form_layout.$name", null);
    }

    /**
     * Get model class by name.
     *
     * @param  string  $name  Model name (e.g., 'product', 'sku')
     * @param  bool  $shouldException  Whether to throw exception if not found
     *
     * @throws ProductException
     */
    public static function getModel(string $name, bool $shouldException = true): ?string
    {
        $model = self::getConfig('models')[$name] ?? null;

        if (blank($model) && $shouldException) {
            throw new ProductException("Model {$name} not found.");
        }

        return $model;
    }

    /**
     * Get Product model class.
     *
     * @return string Models\Product
     */
    public static function getProductModel(): string
    {
        return self::getModel('product');
    }

    /**
     * Get Sku model class.
     *
     * @return string Models\Sku
     */
    // public static function getSkuModel(): string
    // {
    //     return self::getModel('sku');
    // }

    // /**
    //  * Get Variant model class.
    //  *
    //  * @return string Models\Variant
    //  */
    // public static function getVariantModel(): string
    // {
    //     return self::getModel('variant');
    // }

    /**
     * Get file directory path with optional type and date.
     *
     * @param  string|null  $type  Directory type
     */
    public static function getFileDirectory(?string $type = null): string
    {
        return self::getConfig('file_directory', 'sn/product/') . ($type ? $type . '/' : '') . date('Ymd');
    }
}
