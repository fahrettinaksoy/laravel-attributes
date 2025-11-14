<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cat_product', function (Blueprint $table) {
            $table->bigIncrements('product_id');
            $table->string('uuid')->unique();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->string('description');
            $table->string('image_path');
            $table->decimal('price', 15, 2)->nullable()->default('0');
            $table->string('currency_code')->nullable();
            $table->integer('stock')->default('0');
            $table->string('sku')->nullable()->unique();
            $table->integer('category_id')->nullable()->default('0');
            $table->boolean('status')->nullable()->default(true);
            $table->timestamp('created_at')->nullable()->default('0');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable()->default('0');

            $table->foreign('currency_code')
                ->references('code')
                ->on('def_loc_currency')
                ->nullOnDelete();

            $table->foreign('category_id')
                ->references('category_id')
                ->on('def_cat_category')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_product');
    }
};