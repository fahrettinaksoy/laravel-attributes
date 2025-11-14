<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('def_category_translation', function (Blueprint $table) {
            $table->bigIncrements('category_translation_id');
            $table->string('uuid')->unique();
            $table->string('code')->unique();
            $table->string('language_code')->nullable();
            $table->integer('category_id')->nullable()->default('0');
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

            $table->foreign('language_code')
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
        Schema::dropIfExists('def_category_translation');
    }
};