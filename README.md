<h1 align="center"> product </h1>

<p align="center"> Smallnews general product module.</p>


## Installing

```shell
$ composer require wsmallnews/product -vvv
```

## Usage

TODO

## Planning

* 支持多 scope_type 类型共用一个 product，可单独根据店铺下架 down_store_ids
* 支持配送方式 delivery_types delivery_id delivery_fee
* 支持 product 分类，考虑 many to many
* 支持设置限购 limit 
* 多单位支持 show_stock_unit，show_convert_num
* 商品支持服务标签 service_ids （七天无理由退换货）考虑 many to many
* 支持多门店共用商品的 产品可用店铺类型，可用店铺 use_store_type use_store_ids
* 发布渠道

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/wsmallnews/product/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/wsmallnews/product/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT