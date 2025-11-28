<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_review', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('review_id');
            $table->uuid('uuid');
            $table->string('code', 64);
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('account_id')->nullable()->default(0);
            $table->string('author', 150);
            $table->string('content', 800);
            $table->unsignedTinyInteger('rating')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique('uuid', 'uq_cat_review_uuid');
            $table->unique('code', 'uq_cat_review_code');
            $table->index('product_id', 'idx_cat_review_product_id');
            $table->index('account_id', 'idx_cat_review_account_id');
            $table->index('created_by', 'idx_cat_review_created_by');
            $table->index('updated_by', 'idx_cat_review_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_review');
    }
};
