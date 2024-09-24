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
        Schema::create('sn_product_attributes', function (Blueprint $table) {
            $table->comment('产品属性');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('product_id')->default(0)->comment('产品');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('属性上级');
            $table->unsignedBigInteger('attribute_id')->default(0)->comment('属性库');
            $table->unsignedBigInteger('attribute_parent_id')->default(0)->comment('属性库上级');
            $table->tinyInteger('is_default')->default(0)->comment('是否默认');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('排序');
            $table->timestamps();
        });


        Schema::create('sn_product_attribute_repositories', function (Blueprint $table) {
            $table->comment('产品属性库');
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('scope_type', 20)->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('所属属性');
            $table->string('name', 60)->nullable()->comment('名称');
            $table->unsignedInteger('price')->nullable()->comment('价格');
            $table->string('description')->nullable()->comment('描述');
            $table->json('options')->nullable()->comment('选项');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('排序');
            $table->string('status', 20)->comment('商品状态');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_product_attributes');
        Schema::dropIfExists('sn_product_attribute_repositories');
    }
};
