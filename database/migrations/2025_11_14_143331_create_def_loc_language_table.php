<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('def_loc_language', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('language_id');
            $table->uuid('uuid');
            $table->string('code', 10);
            $table->string('name', 255);
            $table->string('description', 500)->nullable();
            $table->string('flag_path', 255);
            $table->string('direction', 10);
            $table->string('directory', 100);
            $table->string('locale', 50);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique('uuid', 'uq_def_loc_language_uuid');
            $table->unique('code', 'uq_def_loc_language_code');
            $table->index('sort_order', 'idx_def_loc_language_sort_order');
            $table->index('status', 'idx_def_loc_language_status');
            $table->index('created_by', 'idx_def_loc_language_created_by');
            $table->index('updated_by', 'idx_def_loc_language_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('def_loc_language');
    }
};
