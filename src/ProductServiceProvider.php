<?php

namespace Wsmallnews\Product;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wsmallnews\Product\Commands\ProductInstallCommand;
// use Wsmallnews\Product\Components\ProductList;
// use Wsmallnews\Product\Components\ProductSku;
// use Wsmallnews\Product\Components\ProductDetail;
// use Wsmallnews\Product\Models\Attribute;
// use Wsmallnews\Product\Models\AttributeRepository;
use Wsmallnews\Product\Models\Product as ProductModel;
// use Wsmallnews\Product\Models\Sku;
// use Wsmallnews\Product\Models\Variant;
// use Wsmallnews\Product\Models\UnitRepository;

class ProductServiceProvider extends PackageServiceProvider
{
    public static string $name = 'sn-product';

    public static string $viewNamespace = 'sn-product';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasConfigFile()
            ->hasMigrations($this->getMigrations())
            ->hasTranslations()
            ->hasViews(static::$viewNamespace);
    }

    public function packageRegistered(): void {}

    /**
     * Boot the package services.
     *
     * @return void
     */
    public function packageBooted()
    {
        // Register model morph map
        Relation::enforceMorphMap([
            // 'sn_product_attribute' => Attribute::class,
            // 'sn_product_attribute_repository' => AttributeRepository::class,
            'sn_product' => ProductModel::class,
            // 'sn_product_sku' => Sku::class,
            // 'sn_product_variant' => Variant::class,
            // 'sn_product_unit_repository' => UnitRepository::class,
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
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/comment/{$file->getFilename()}"),
                ], 'product-stubs');
            }
        }

        // Livewire Components
        // Livewire::component('sn-product-list', ProductList::class);
        // Livewire::component('sn-product-sku', ProductSku::class);
        // Livewire::component('sn-product-detail', ProductDetail::class);
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
            // Css::make('product-styles', __DIR__ . '/../resources/dist/product.css')->loadedOnRequest(),
            // Js::make('product-scripts', __DIR__ . '/../resources/dist/product.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            ProductInstallCommand::class,
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
            // '2025_01_20_113316_create_sn_product_attributes_table',
            // '2025_01_20_113316_create_sn_product_skus_table',
            // '2025_01_20_113316_create_sn_product_unit_repositories_table',
            'create_sn_products_table',
        ];
    }
}
