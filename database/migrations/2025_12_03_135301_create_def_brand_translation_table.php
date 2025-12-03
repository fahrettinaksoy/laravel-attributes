<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('def_brand_translation', function (Blueprint $table) {
            $table->bigIncrements('brand_translation_id');
            $table->string('uuid')->unique();
            $table->string('code')->unique();
            $table->unsignedBigInteger('brand_id');
            $table->string('language_code');
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
        Schema::dropIfExists('def_brand_translation');
    }
};
