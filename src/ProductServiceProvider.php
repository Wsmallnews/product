<?php

namespace Wsmallnews\Product;

use Filament\Forms\Components\TextInput;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wsmallnews\Product\Commands\ProductInstallCommand;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Models\Product as ProductModel;
use Wsmallnews\Product\Models\Spec as SpecModel;
use Wsmallnews\Product\Models\Variant as VariantModel;
use Wsmallnews\Support\Facades\ScheduledTask;

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
            'sn_product' => ProductModel::class,
            'sn_product_spec' => SpecModel::class,
            'sn_product_variant' => VariantModel::class,
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

        // 注册 Product 的定时调度动作（publish / unpublish / price_change）
        ScheduledTask::registers('sn_product', [
            [
                'action' => 'publish',
                'label' => '定时上架',
                'forms' => fn () => [],
                'handler' => fn ($task, ?array $payload): bool => $task->schedulable->update([
                    'status' => ProductStatus::Up,
                    'published_at' => $task->schedulable->published_at ?? now(),
                ]),
            ],
            [
                'action' => 'unpublish',
                'label' => '定时下架',
                'forms' => fn () => [],
                'handler' => fn ($task, ?array $payload): bool => $task->schedulable->update([
                    'status' => ProductStatus::Down,
                ]),
            ],
            [
                'action' => 'price_change',
                'label' => '定时改价',
                // 注册时声明自定义字段（价格 / 原价）
                'forms' => fn () => [
                    TextInput::make('price')->label('促销价')->numeric()->required(),
                    TextInput::make('original_price')->label('原价')->numeric(),
                ],
                // handler 本次预留，多规格逻辑待 SKU/Variant 完善后实现
                'handler' => function ($task, ?array $payload): bool {
                    $updates = array_filter([
                        'price' => $payload['price'] ?? null,
                        'original_price' => $payload['original_price'] ?? null,
                    ], fn ($v) => $v !== null);

                    return $task->schedulable->update($updates);
                },
            ],
        ]);
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
            // '2025_01_20_113316_create_sn_product_unit_repositories_table',
            'create_sn_products_table',
            'create_sn_product_specs_table',
            'create_sn_product_variants_table',
        ];
    }
}
