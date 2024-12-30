<?php

namespace Wsmallnews\Product;

use Wsmallnews\Support\Traits\Resources\CanSetResource;

class Product
{
    use CanSetResource;

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

}
