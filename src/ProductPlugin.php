<?php

namespace Wsmallnews\Product;

use Filament\Facades\Filament;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Wsmallnews\Product\Resources\ProductResource;
use Wsmallnews\Product\Resources\AttributeRepositoryResource;
use Wsmallnews\Product\Resources\UnitRepositoryResource;

class ProductPlugin implements Plugin
{

    /**
     * panel 加载插件 Self::make()
     *
     * @return static
     */
    public static function make(): static
    {
        return app(static::class);
    }


    /**
     * panel 获取当前插件 Self::get()->method()
     *
     * @return static
     */
    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }


    public function getId(): string
    {
        return 'sn_product';
    }


    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                ProductResource::class,
                AttributeRepositoryResource::class,
                UnitRepositoryResource::class,
                // PostResource::class,
                // CategoryResource::class,
            ])
            ->pages([
                // Settings::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
