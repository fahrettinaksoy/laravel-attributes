<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
            $table->boolean('status')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_review');
    }
};
