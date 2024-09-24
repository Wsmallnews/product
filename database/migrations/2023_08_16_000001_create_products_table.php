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
        Schema::create('sn_products', function (Blueprint $table) {
            $table->comment('产品');
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('scope_type', 20)->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');
            $table->string('type', 30)->nullable()->comment('类型');
            $table->string('title')->nullable()->comment('标题');
            $table->string('subtitle')->nullable()->comment('副标题');
            $table->string('image')->nullable()->comment('封面');
            $table->json('images')->nullable()->comment('轮播图');
            $table->string('sku_type', 20)->comment('sku类型');
            
            $table->unsignedInteger('original_price')->default(0)->comment('原价');
            $table->unsignedInteger('price')->default(0)->comment('现价');

            $table->string('stock_type', 20)->comment('库存类型');
            $table->string('stock_unit', 20)->comment('库存单位');
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
        Schema::dropIfExists('sn_product');
    }
};
