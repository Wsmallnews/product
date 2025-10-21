<?php

namespace Wsmallnews\Product\Resources\Traits;

use Closure;
use Filament\Facades\Filament;
use Wsmallnews\Product\ProductPlugin;
use Illuminate\Support\Str;

trait SetResourceTraitTrait
{

    public static function getModelLabel(): string
    {
        return self::currentPlugin()->getLabel(self::currentResourceName());
    }

    public static function getPluralModelLabel(): string
    {
        return self::currentPlugin()->getPluralLabel(self::currentResourceName());
    }
    
    public static function getSlug(): string
    {
        $slug = self::currentPlugin()->getSlug(self::currentResourceName());
        return filled($slug) ? $slug : parent::getSlug();
    }

    public static function getNavigationIcon(): string
    {
        return self::currentPlugin()->getNavigationIcon(self::currentResourceName());
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel()) ?? Str::title(static::getModelLabel());
    }

    public static function getNavigationSort(): ?int
    {
        return self::currentPlugin()->getNavigationSort(self::currentResourceName());
    }

    public static function getNavigationGroup(): ?string
    {
        return self::currentPlugin()->getNavigationGroup(self::currentResourceName());
    }

    public static function getNavigationBadge(): ?string
    {
        return self::currentPlugin()->getNavigationCountBadge(self::currentResourceName()) ?
            number_format(static::getModel()::count()) : null;
    }


    /**
     * 当前插件
     *
     * @return Filament\Contracts\Plugin
     */
    protected static function currentPlugin()
    {
        $plugin = Filament::getCurrentPanel()?->getPlugin('sn-product');

        return $plugin;
    }

    /**
     * 当前资源名称
     * 
     * @return string
     */
    protected static function currentResourceName()
    {
        $basename = class_basename(static::class);

        return Str::snake($basename);
    }
}