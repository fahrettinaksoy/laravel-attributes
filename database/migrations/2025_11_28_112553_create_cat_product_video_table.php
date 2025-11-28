<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cat_product_video', function (Blueprint $table) {
            $table->bigIncrements('product_video_id');
            $table->unsignedBigInteger('product_id');
            $table->string('uuid')->unique();
            $table->string('code')->unique();
            $table->string('source');
            $table->string('content');
            $table->timestamp('created_at')->nullable()->default('0');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('product_id')
                ->references('product_id')
                ->on('cat_product')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_product_video');
    }
};