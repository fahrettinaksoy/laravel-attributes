<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_product_image', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('product_image_id');
            $table->unsignedBigInteger('product_id')->nullable()->default(0);
            $table->uuid('uuid');
            $table->string('code', 64);
            $table->string('file_path', 255);
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique('uuid', 'uq_cat_product_image_uuid');
            $table->unique('code', 'uq_cat_product_image_code');
            $table->index('product_id', 'idx_cat_product_image_product_id');
            $table->index('created_by', 'idx_cat_product_image_created_by');
            $table->index('updated_by', 'idx_cat_product_image_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_product_image');
    }
};
