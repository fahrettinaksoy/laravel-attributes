<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cat_product_image_translation', function (Blueprint $table) {
            $table->bigIncrements('product_image_translation_id');
            $table->unsignedBigInteger('product_image_id');
            $table->string('uuid')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('summary')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->nullable()->default('0');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('product_image_id')
                ->references('product_image_id')
                ->on('cat_product_image')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_product_image_translation');
    }
};