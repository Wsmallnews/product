<?php

namespace Wsmallnews\Product\Commands;

use Wsmallnews\Support\Commands\PackageInstallCommand;

class ProductInstallCommand extends PackageInstallCommand
{
    protected string $packageName = 'sn-product';
}
