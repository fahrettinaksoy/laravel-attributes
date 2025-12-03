<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cat_product_translation', function (Blueprint $table) {
            $table->bigIncrements('product_translation_id');
            $table->unsignedBigInteger('product_id');
            $table->uuid('uuid')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('summary')->nullable();
            $table->string('description')->nullable();
            $table->string('slug');
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keyword')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_product_translation');
    }
};
