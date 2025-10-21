<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sn_product_types', function (Blueprint $table) {
            $table->comment('产品类型');
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('scope_type', 20)->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');

            $table->string('name')->nullable()->comment('类型名称');
            $table->string('product_type', 20)->comment('产品类型');          // 实物商品，和虚拟商品
            $table->string('stock_type', 20)->comment('库存类型');            // 不限库存，有限库存，多单位，库存管理




            $table->string('is_cart', 20)->comment('是否可加入购物车');
            // 待解决问题：
            // 1. 如果有多种类型可以加入购物车，是否允许不同类型的商品可以一起下单


            $table->string('delivery', 20)->comment('配送方式');            
            
            // 1、从 delivery 获取可用的配送方式
            // 2、根据配送方式决定是否可自动发货，或者只能手动发货
            




            $table->string('sku_type', 20)->comment('sku类型');
            
            $table->unsignedInteger('collects')->default(0)->comment('收藏数量');
            $table->unsignedInteger('views')->default(0)->comment('浏览数量');
            $table->unsignedInteger('show_sales')->default(0)->comment('显示销量');
            $table->json('params')->nullable()->comment('参数');
            $table->text('content')->nullable()->comment('详情');
            $table->string('status', 20)->comment('商品状态');
            $table->json('options')->nullable()->comment('选项');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('排序');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_product_types');
    }
};
