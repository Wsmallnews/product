<?php

namespace Wsmallnews\Product;

use Filament\Facades\Filament;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Wsmallnews\Product\Commands\ProductCommand;
use Wsmallnews\Product\Components\ProductList;
use Wsmallnews\Product\Components\ProductSku;
use Wsmallnews\Product\Components\ProductDetail;
use Wsmallnews\Product\Testing\TestsProduct;
use Wsmallnews\Product\Models\Attribute;
use Wsmallnews\Product\Models\AttributeRepository;
use Wsmallnews\Product\Models\Product as ProductModel;
use Wsmallnews\Product\Models\Sku;
use Wsmallnews\Product\Models\SkuPrice;
use Wsmallnews\Product\Models\UnitRepository;

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
            $package->runsMigrations();
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
            'sn_product_attribute' => Attribute::class,
            'sn_product_attribute_repository' => AttributeRepository::class,
            'sn_product' => ProductModel::class,
            'sn_product_sku' => Sku::class,
            'sn_product_sku_price' => SkuPrice::class,
            'sn_product_unit_repository' => UnitRepository::class,
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

        Livewire::component('sn-product-list', ProductList::class);

        Livewire::component('sn-product-sku', ProductSku::class);

        Livewire::component('sn-product-detail', ProductDetail::class);

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
            // AlpineComponent::make('product', __DIR__ . '/../resources/dist/components/product.js'),
            Css::make('product-styles', __DIR__ . '/../resources/dist/product.css'),
            // Js::make('product-scripts', __DIR__ . '/../resources/dist/product.js'),
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
            '2025_01_20_113316_create_sn_product_attributes_table',
            '2025_01_20_113316_create_sn_product_skus_table',
            '2025_01_20_113316_create_sn_product_unit_repositories_table',
            '2025_01_20_113316_create_sn_products_table'
        ];
    }
}
