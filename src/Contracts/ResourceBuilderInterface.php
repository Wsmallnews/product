<?php

namespace Wsmallnews\Product\Contracts;

use Closure;

/**
 * 资源构建 interface
 */
interface ResourceBuilderInterface
{

    /**
     * form schema
     *
     * @return array
     */
    public function schema(): array;


    /**
     * wizard steps schema
     *
     * @return array
     */
    public function getWizardSteps(): array;


    /**
     * 表单table columns
     *
     * @return array
     */
    public function columns(): array;



    /**
     * 表单table filters
     *
     * @return array
     */
    public function filters(): array;
}
