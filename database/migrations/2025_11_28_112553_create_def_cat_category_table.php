<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('def_cat_category', function (Blueprint $table) {
            $table->bigIncrements('category_id');
            $table->string('uuid')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('parent_id')->default('0');
            $table->unsignedBigInteger('layout_id')->default('0');
            $table->unsignedBigInteger('membership')->default('0');
            $table->boolean('status')->nullable()->default(true);
            $table->timestamp('created_at')->nullable()->default('0');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('parent_id')
                ->references('category_id')
                ->on('def_cat_category')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('def_cat_category');
    }
};