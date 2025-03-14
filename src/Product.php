<?php

namespace Wsmallnews\Product;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Wsmallnews\Support\Traits\Resources\CanSetResource;

class Product
{
    use CanSetResource;

    public static $imageDirectory;

    public static $currency = 'CNY';

    public static $diskName = null;


    /**
     * 设置上传磁盘驱动
     *
     * @param string $name
     * @return void
     */
    public static function setDiskName($name)
    {
        self::$diskName = $name;
    }

    /**
     * 获取磁盘驱动
     *
     * @return string
     */
    public static function getDiskName()
    {
        return self::$diskName ?: config('filament.default_filesystem_disk');
    }


    /**
     * 获取磁盘对应的访问地址
     *
     * @return string
     */
    public static function getDiskUrl()
    {
        $diskName = self::getDiskName() ?: config('filesystems.default');
        return config('filesystems.disks.' . $diskName . '.url' , config('app.url'));
    }


    /**
     * 获取磁盘对应的访问地址
     *
     * @return string
     */
    public static function filesUrl($originalFiles)
    {
        $files = Arr::wrap($originalFiles);

        $files = Arr::map($files, function ($file) {
            return self::getDiskUrl() . Str::start($file, '/');
        });

        return Arr::accessible($originalFiles) ? $files : Arr::first($files);
    }


    public static function setImageDirectory($imageDirectory)
    {
        self::$imageDirectory = $imageDirectory;
    }


    public static function getImageDirectory()
    {
        return self::$imageDirectory ?: 'filaments/products/' . date('Ymd');
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
