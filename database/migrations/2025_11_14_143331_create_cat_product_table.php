<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_product', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('product_id');
            $table->uuid('uuid');
            $table->string('code', 64);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->string('currency_code', 3);
            $table->unsignedInteger('stock')->default(0);
            $table->string('sku', 64);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique('uuid', 'uq_cat_product_uuid');
            $table->unique('code', 'uq_cat_product_code');
            $table->unique('sku', 'uq_cat_product_sku');
            $table->index('category_id', 'idx_cat_product_category_id');
            $table->index('brand_id', 'idx_cat_product_brand_id');
            $table->index('created_by', 'idx_cat_product_created_by');
            $table->index('updated_by', 'idx_cat_product_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_product');
    }
};
