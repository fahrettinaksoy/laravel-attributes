<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_product_image', function (Blueprint $table) {
            $table->bigIncrements('product_image_id');
            $table->unsignedBigInteger('product_id')->nullable()->default('0');
            $table->uuid('uuid')->unique();
            $table->string('code')->unique();
            $table->string('file_path');
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_product_image');
    }
};
