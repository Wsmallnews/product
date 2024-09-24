<?php

namespace Wsmallnews\Product\Contracts;

use Closure;

/**
 * 产品布局 interface
 */
interface LayoutInterface
{

    /**
     * form schema
     *
     * @return array
     */
    public function schema(): array;
}