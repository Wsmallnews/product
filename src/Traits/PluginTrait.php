<?php

namespace Wsmallnews\Product\Traits;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Support\Arr;

trait PluginTrait
{

    use EvaluatesClosures;

    public $resourceConfig = [
        // 'resource_name' => [        // config 存储格式
        //     'resource' => \Wsmallnews\Product\Resources\ProductResource::class,
        //     'label' => '产品',
        //     'plural_label' => '产品管理',
        //     'slug' => '/products',
        //     'navigation_item' => true,
        //     'navigation_group' => null,
        //     'navigation_icon' => 'heroicon-o-shield-check',
        //     'navigation_sort' => null,
        //     // 'default_sort_column'    => 'id',
        //     // 'default_sort_direction' => 'desc',
        //     'navigation_count_badge' => false,
        // ]
    ];


    public function getResource($resource_name = 'resource'): string
    {
        return $this->resourceConfig[$resource_name]['resource'] ?? $this->getConfig($resource_name . '.resource');
    }

    public function getLabel($resource_name = 'resource'): string
    {
        return $this->evaluate($this->resourceConfig[$resource_name]['label'] ?? null) ?? $this->getConfig($resource_name . '.label');
    }

    public function getPluralLabel($resource_name = 'resource'): string
    {
        return $this->evaluate($this->resourceConfig[$resource_name]['plural_label'] ?? null) ?? $this->getConfig($resource_name . '.plural_label');
    }

    public function getSlug($resource_name = 'resource'): string
    {
        return $this->resourceConfig[$resource_name]['slug'] ?? $this->getConfig($resource_name . '.slug');
    }

    public function getNavigationItem($resource_name = 'resource'): bool
    {
        return $this->evaluate($this->resourceConfig[$resource_name]['navigation_item'] ?? null) ?? $this->getConfig($resource_name . '.navigation_item');
    }

    public function getNavigationGroup($resource_name = 'resource'): ?string
    {
        return $this->evaluate($this->resourceConfig[$resource_name]['navigation_group'] ?? null) ?? $this->getConfig($resource_name . '.navigation_group');
    }

    public function getNavigationIcon($resource_name): ?string
    {
        return $this->resourceConfig[$resource_name]['navigation_icon'] ?? $this->getConfig($resource_name . '.navigation_icon');
    }

    public function getNavigationSort($resource_name): ?int
    {
        return $this->resourceConfig[$resource_name]['navigation_sort'] ?? $this->getConfig($resource_name . '.navigation_sort');
    }


    public function getNavigationCountBadge($resource_name = 'resource'): ?bool
    {
        return $this->resourceConfig[$resource_name]['navigation_count_badge'] ?? $this->getConfig($resource_name . '.navigation_count_badge');
    }



    public function resource(string $resource, $resource_name = 'resource'): static
    {
        $this->resourceConfig[$resource_name]['resource'] = $resource;

        return $this;
    }

    public function label(string|Closure $label, $resource_name = 'resource'): static
    {
        $this->resourceConfig[$resource_name]['label'] = $label;

        return $this;
    }

    public function pluralLabel(string|Closure $label, $resource_name = 'resource'): static
    {
        $this->resourceConfig[$resource_name]['plural_label'] = $label;

        return $this;
    }

    public function slug(string $slug, $resource_name = 'resource'): static
    {
        $this->resourceConfig[$resource_name]['slug'] = $slug;

        return $this;
    }

    public function navigationItem(Closure|bool $value = true, $resource_name = 'resource'): static
    {
        $this->resourceConfig[$resource_name]['navigation_item'] = $value;

        return $this;
    }

    public function navigationGroup(string|Closure|null $group = null, $resource_name = 'resource'): static
    {
        $this->resourceConfig[$resource_name]['navigation_group'] = $group;

        return $this;
    }

    public function navigationIcon(string $icon, $resource_name = 'resource'): static
    {
        $this->resourceConfig[$resource_name]['navigation_icon'] = $icon;

        return $this;
    }

    public function navigationSort(int $order, $resource_name = 'resource'): static
    {
        $this->resourceConfig[$resource_name]['navigation_sort'] = $order;

        return $this;
    }


    public function navigationCountBadge(bool $show = true, $resource_name = 'resource'): static
    {
        $this->resourceConfig[$resource_name]['navigation_count_badge'] = $show;

        return $this;
    }




    protected function getConfig($name = null)
    {
        $config = config(app(static::class)->getId());

        return $name ? Arr::get($config, $name, null) : $config;
    }
}