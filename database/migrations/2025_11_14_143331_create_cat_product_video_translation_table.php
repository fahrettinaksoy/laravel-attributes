<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_product_video_translation', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('product_video_translation_id');
            $table->unsignedBigInteger('product_video_id');
            $table->uuid('uuid');
            $table->string('code', 64);
            $table->string('name', 255)->nullable();
            $table->string('summary', 500)->nullable();
            $table->string('description', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique('uuid', 'uq_cat_product_video_translation_uuid');
            $table->unique('code', 'uq_cat_product_video_translation_code');
            $table->index('product_video_id', 'idx_cat_product_video_translation_video_id');
            $table->index('created_by', 'idx_cat_product_video_translation_created_by');
            $table->index('updated_by', 'idx_cat_product_video_translation_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_product_video_translation');
    }
};
