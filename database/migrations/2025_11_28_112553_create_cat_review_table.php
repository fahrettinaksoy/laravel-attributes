<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cat_review', function (Blueprint $table) {
            $table->bigIncrements('review_id');
            $table->string('uuid')->unique();
            $table->string('code')->unique();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('account_id')->nullable()->default('0');
            $table->string('author');
            $table->string('content');
            $table->unsignedTinyInteger('rating')->default('0');
            $table->boolean('status')->nullable()->default(true);
            $table->timestamp('created_at')->nullable()->default('0');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('product_id')
                ->references('product_id')
                ->on('cat_product')
                ->nullOnDelete();

            $table->foreign('account_id')
                ->references('account_id')
                ->on('acc_account')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_review');
    }
};