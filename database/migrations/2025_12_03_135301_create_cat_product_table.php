<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cat_product', function (Blueprint $table) {
            $table->bigIncrements('product_id');
            $table->uuid('uuid')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('price', 15, 2)->nullable()->default('0');
            $table->string('currency_code');
            $table->integer('stock')->default('0');
            $table->string('sku')->unique();
            $table->integer('category_id')->default('0');
            $table->boolean('status')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable()->default('0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_product');
    }
};
