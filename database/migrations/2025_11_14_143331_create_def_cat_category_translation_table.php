<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('def_category_translation', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('category_translation_id');
            $table->uuid('uuid');
            $table->string('code', 64);
            $table->string('language_code', 5);
            $table->unsignedBigInteger('category_id');
            $table->string('name', 255);
            $table->string('summary', 500)->nullable();
            $table->string('description', 500)->nullable();
            $table->string('slug', 255);
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('meta_keyword', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique('uuid', 'uq_def_category_translation_uuid');
            $table->unique('code', 'uq_def_category_translation_code');
            $table->index('category_id', 'idx_def_category_translation_category_id');
            $table->index('language_code', 'idx_def_category_translation_language_code');
            $table->index('created_by', 'idx_def_category_translation_created_by');
            $table->index('updated_by', 'idx_def_category_translation_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('def_category_translation');
    }
};
