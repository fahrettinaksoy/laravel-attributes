<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_product_video_translation', function (Blueprint $table) {
            $table->bigIncrements('product_video_translation_id');
            $table->unsignedBigInteger('product_video_id');
            $table->string('uuid')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('summary')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_product_video_translation');
    }
};
