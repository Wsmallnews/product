<?php

namespace Wsmallnews\Product;

use Filament\Facades\Filament;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Wsmallnews\Product\Commands\ProductCommand;
use Wsmallnews\Product\Testing\TestsProduct;

class ProductServiceProvider extends PackageServiceProvider
{
    public static string $name = 'sn-product';

    public static string $viewNamespace = 'sn-product';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('wsmallnews/product');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }



    public function packageRegistered(): void {}

    /**
     * 引导包完成 (boot 方法的结束)
     *
     * @return void
     */
    public function packageBooted()
    {
        // 注册模型别名
        Relation::enforceMorphMap([
            'sn_product' => 'Wsmallnews\Product\Models\Product',
            'sn_product_sku' => 'Wsmallnews\Product\Models\Sku',
            'sn_product_sku_price' => 'Wsmallnews\Product\Models\SkuPrice',
        ]);

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            // foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
            //     $this->publishes([
            //         $file->getRealPath() => base_path("stubs/product/{$file->getFilename()}"),
            //     ], 'product-stubs');
            // }
        }

        // Testing
        Testable::mixin(new TestsProduct);
    }


    protected function getAssetPackageName(): ?string
    {
        return 'wsmallnews/product';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('order', __DIR__ . '/../resources/dist/components/order.js'),
            // Css::make('order-styles', __DIR__ . '/../resources/dist/order.css'),
            // Js::make('order-scripts', __DIR__ . '/../resources/dist/order.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            // ProductCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_sn_order_table',
        ];
    }
}
