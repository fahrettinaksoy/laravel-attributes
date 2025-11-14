<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cat_product_translation', function (Blueprint $table) {
            $table->bigIncrements('product_translation_id');
            $table->integer('product_id')->nullable()->default('0');
            $table->string('uuid')->unique();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->string('summary');
            $table->string('description');
            $table->string('slug')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keyword')->nullable();
            $table->timestamp('created_at')->nullable()->default('0');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable()->default('0');

            $table->foreign('product_id')
                ->references('product_id')
                ->on('cat_product')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_product_translation');
    }
};