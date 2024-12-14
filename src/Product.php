<?php

namespace Wsmallnews\Product;

use Illuminate\Support\Str;
use Wsmallnews\Product\Resources\ProductResource;
use Wsmallnews\Product\Resources\AttributeRepositoryResource;
use Wsmallnews\Product\Resources\UnitRepositoryResource;

class Product 
{

    public static $image_directory;

    public static $currency = 'CNY';


    public static function setImageDirectory($image_directory)
    {
        self::$image_directory = $image_directory;
    }


    public static function getImageDirectory()
    {
        return self::$image_directory ?: 'filaments/products/' . date('Ymd');
    }


    public static function setCurrency($currency)
    {
        self::$currency = $currency;
    }


    public static function getCurrency()
    {
        return self::$currency;
    }


    // 批量设置导航
    public static function setResources(array $resourceInfos)
    {
        foreach ($resourceInfos['resources'] as $resource => $resourceInfo) {
            $resourceInfo = array_merge($resourceInfos['group_info'], $resourceInfo);

            foreach ($resourceInfo as $attributeKey => $attributeValue) {
                $attributeKey = Str::camel($attributeKey);
                if (method_exists($resource, $attributeKey)) {
                    $resource::$attributeKey($attributeValue);
                } else {
                    $resource::setAttribute($attributeKey, $attributeValue);
                }
            }
        }

        // print_r(ProductResource::getNavigationParentItem());exit;
    }




}
