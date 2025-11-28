<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('def_cat_brand', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('brand_id');
            $table->uuid('uuid');
            $table->string('code', 64);
            $table->string('name', 255);
            $table->string('description', 500)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->unsignedBigInteger('layout_id')->default(0);
            $table->unsignedBigInteger('membership')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique('uuid', 'uq_def_cat_brand_uuid');
            $table->unique('code', 'uq_def_cat_brand_code');
            $table->index('layout_id', 'idx_def_cat_brand_layout_id');
            $table->index('membership', 'idx_def_cat_brand_membership');
            $table->index('created_by', 'idx_def_cat_brand_created_by');
            $table->index('updated_by', 'idx_def_cat_brand_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('def_cat_brand');
    }
};
