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

        Schema::create('sn_product_skus', function (Blueprint $table) {
            $table->comment('产品规格');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('product_id')->default(0)->comment('产品');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('上级');
            $table->string('name')->nullable()->comment('名称');
            $table->string('image')->nullable()->comment('规格图');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('排序');
        });


        Schema::create('sn_product_sku_prices', function (Blueprint $table) {
            $table->comment('产品规格价格');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('product_id')->default(0)->comment('产品');
            $table->string('product_sku_ids')->nullable()->comment('规格');
            $table->string('product_sku_text')->nullable()->comment('规格中文');
            $table->string('product_sn')->nullable()->comment('货号');
            $table->string('image')->nullable()->comment('规格封面');
            $table->string('sku_type', 20)->comment('sku类型');
            $table->unsignedInteger('original_price')->default(0)->comment('原价');
            $table->unsignedInteger('cost_price')->default(0)->comment('成本价');
            $table->unsignedInteger('price')->default(0)->comment('现价');
            $table->integer('stock')->default(0)->comment('库存');
            $table->integer('sales')->default(0)->comment('销量');
            $table->decimal('weight', 10, 2)->default(0)->comment('重量KG');
            $table->string('status', 20)->comment('规格状态');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_product_skus');

        Schema::dropIfExists('sn_product_sku_prices');
    }
};
