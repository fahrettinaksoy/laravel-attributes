<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PRODUCT_TABLE = 'product';
    private const PRODUCT_IMAGE_TABLE = 'product_image';
    private const PRODUCT_VIDEO_TABLE = 'product_video';
    private const PRODUCT_TRANSLATION_TABLE = 'product_translation';
    private const LANGUAGE_TABLE = 'language';
    private const CATEGORY_TABLE = 'category';

    public function up(): void
    {
        Schema::create(self::LANGUAGE_TABLE, function (Blueprint $table) {
            $table->bigIncrements('language_id')->comment('Primary key for language');
            $table->string('code', 5)->unique()->comment('Language code (e.g., en, tr, de)');
            $table->string('name')->comment('Language name');
            $table->string('locale', 10)->nullable()->comment('Locale code (e.g., en_US, tr_TR)');
            $table->string('flag')->nullable()->comment('Flag icon path');
            $table->integer('sort_order')->default(0)->comment('Sort order');
            $table->boolean('status')->default(true)->comment('Status');
            $table->timestamps();

            $table->index('code');
            $table->index('status');
            $table->index('sort_order');
        });

        Schema::create(self::CATEGORY_TABLE, function (Blueprint $table) {
            $table->bigIncrements('category_id')->comment('Primary key for category');
            $table->string('name')->comment('Category name');
            $table->string('code', 100)->unique()->comment('Category code');
            $table->text('description')->nullable()->comment('Category description');
            $table->string('image')->nullable()->comment('Category image');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('Parent category ID');
            $table->integer('sort_order')->default(0)->comment('Sort order');
            $table->boolean('status')->default(true)->comment('Status');
            $table->timestamps();

            $table->index('code');
            $table->index('parent_id');
            $table->index('status');
            $table->index('sort_order');
        });

        Schema::create(self::PRODUCT_TABLE, function (Blueprint $table) {
            $table->bigIncrements('product_id')->comment('Primary key for product');
            $table->string('name')->comment('Product name');
            $table->string('code', 100)->unique()->comment('Product code');
            $table->text('description')->nullable()->comment('Product description');
            $table->string('image')->nullable()->comment('Main product image');
            $table->decimal('price', 10, 2)->comment('Product price');
            $table->string('currency', 3)->default('TRY')->comment('Currency code');
            $table->integer('stock')->default(0)->comment('Stock quantity');
            $table->string('sku', 100)->unique()->nullable()->comment('Stock keeping unit');
            $table->unsignedBigInteger('category_id')->nullable()->comment('Category ID');
            $table->boolean('status')->default(true)->comment('Status');
            $table->timestamps();

            $table->index('code');
            $table->index('sku');
            $table->index('category_id');
            $table->index('status');
        });

        Schema::create(self::PRODUCT_IMAGE_TABLE, function (Blueprint $table) {
            $table->bigIncrements('product_image_id')->comment('Primary key for product image record');
            $table->unsignedBigInteger('product_id')->comment('Product ID');
            $table->string('image_path')->comment('Image file path');
            $table->string('alt_text')->nullable()->comment('Image alt text');
            $table->integer('sort_order')->default(0)->comment('Sort order');
            $table->timestamps();

            $table->index('product_id');
            $table->index('sort_order');
        });

        Schema::create(self::PRODUCT_VIDEO_TABLE, function (Blueprint $table) {
            $table->bigIncrements('product_video_id')->comment('Primary key for product video record');
            $table->unsignedBigInteger('product_id')->comment('Product ID');
            $table->string('video_url')->comment('Video URL');
            $table->string('title')->nullable()->comment('Video title');
            $table->integer('sort_order')->default(0)->comment('Sort order');
            $table->timestamps();

            $table->index('product_id');
            $table->index('sort_order');
        });

        Schema::create(self::PRODUCT_TRANSLATION_TABLE, function (Blueprint $table) {
            $table->bigIncrements('product_translation_id')->comment('Primary key for product translation record');
            $table->unsignedBigInteger('product_id')->comment('Product ID');
            $table->string('language_code', 5)->comment('Language code (e.g., en, tr, de)');
            $table->string('name')->comment('Translated product name');
            $table->text('description')->nullable()->comment('Translated product description');
            $table->string('meta_title')->nullable()->comment('SEO meta title');
            $table->text('meta_description')->nullable()->comment('SEO meta description');
            $table->string('meta_keywords')->nullable()->comment('SEO meta keywords');
            $table->timestamps();

            $table->index('product_id');
            $table->index('language_code');
            $table->unique(['product_id', 'language_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::PRODUCT_TRANSLATION_TABLE);
        Schema::dropIfExists(self::PRODUCT_VIDEO_TABLE);
        Schema::dropIfExists(self::PRODUCT_IMAGE_TABLE);
        Schema::dropIfExists(self::PRODUCT_TABLE);
        Schema::dropIfExists(self::CATEGORY_TABLE);
        Schema::dropIfExists(self::LANGUAGE_TABLE);
    }
};
