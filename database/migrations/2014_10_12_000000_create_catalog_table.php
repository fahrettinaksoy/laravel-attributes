<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    private const TABLE_DEF_CAT_LANGUAGE = 'def_cat_language';
    private const TABLE_DEF_CAT_CATEGORY = 'def_cat_category';
    private const TABLE_CAT_PRODUCT = 'cat_product';
    private const TABLE_CAT_PRODUCT_IMAGE = 'cat_product_image';
    private const TABLE_CAT_PRODUCT_VIDEO = 'cat_product_video';
    private const TABLE_CAT_PRODUCT_TRANSLATION = 'cat_product_translation';

    public function up(): void
    {
        Schema::create(self::TABLE_DEF_CAT_LANGUAGE, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment('Language definition table');

            $table->bigIncrements('language_id')->comment('Primary key');
            $table->uuid('uuid')->unique()->comment('Unique universal identifier');
            $table->string('code', 5)->unique()->comment('Language code (e.g. en, tr)');
            $table->string('name')->comment('Language name');
            $table->string('locale', 10)->nullable()->comment('Locale code (e.g. en_US)');
            $table->string('flag')->nullable()->comment('Flag image path');
            $table->unsignedInteger('sort_order')->default(0)->comment('Sort order');
            $table->boolean('is_active')->default(true)->comment('Active status');
            $table->timestamp('created_at')->useCurrent()->comment('Creation timestamp');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Created by user ID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('Updated by user ID');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->comment('Update timestamp');

            $table->index(['code', 'is_active', 'sort_order'], 'idx_language_lookup');
        });

        Schema::create(self::TABLE_DEF_CAT_CATEGORY, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment('Product category table');

            $table->bigIncrements('category_id')->comment('Primary key');
            $table->uuid('uuid')->unique()->comment('Unique universal identifier');
            $table->string('name')->comment('Category name');
            $table->string('code', 100)->unique()->comment('Unique category code');
            $table->text('description')->nullable()->comment('Category description');
            $table->string('image')->nullable()->comment('Category image path');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Parent category ID');
            $table->unsignedInteger('sort_order')->default(0)->comment('Sort order');
            $table->boolean('is_active')->default(true)->comment('Active status');
            $table->timestamp('created_at')->useCurrent()->comment('Creation timestamp');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Created by user ID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('Updated by user ID');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->comment('Update timestamp');

            $table->index(['code', 'parent_id', 'is_active', 'sort_order'], 'idx_category_lookup');
        });

        Schema::create(self::TABLE_CAT_PRODUCT, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment('Main product table');

            $table->bigIncrements('product_id')->comment('Primary key');
            $table->uuid('uuid')->unique()->comment('Unique universal identifier');
            $table->string('name')->comment('Default product name');
            $table->string('code', 100)->unique()->comment('Unique product code');
            $table->text('description')->nullable()->comment('Product description');
            $table->string('image')->nullable()->comment('Main image path');
            $table->decimal('price', 12, 2)->default(0)->comment('Product price');
            $table->char('currency_code', 3)->default('TRY')->comment('Currency code (ISO 4217)');
            $table->integer('stock')->default(0)->comment('Stock quantity');
            $table->string('sku', 100)->nullable()->unique()->comment('Stock keeping unit');
            $table->unsignedBigInteger('category_id')->nullable()->comment('Linked category ID');
            $table->boolean('is_active')->default(true)->comment('Active status');
            $table->timestamp('created_at')->useCurrent()->comment('Creation timestamp');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Created by user ID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('Updated by user ID');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->comment('Update timestamp');

            $table->index(['code', 'sku', 'category_id', 'is_active'], 'idx_product_lookup');
        });

        Schema::create(self::TABLE_CAT_PRODUCT_IMAGE, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment('Product image table');

            $table->bigIncrements('product_image_id')->comment('Primary key');
            $table->uuid('uuid')->unique()->comment('Unique universal identifier');
            $table->unsignedBigInteger('product_id')->comment('Related product ID');
            $table->string('path')->comment('Image file path');
            $table->string('alt_text')->nullable()->comment('Alternative text for image');
            $table->unsignedInteger('sort_order')->default(0)->comment('Sort order');
            $table->timestamp('created_at')->useCurrent()->comment('Creation timestamp');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Created by user ID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('Updated by user ID');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->comment('Update timestamp');

            $table->index(['product_id', 'sort_order'], 'idx_product_image');
        });

        Schema::create(self::TABLE_CAT_PRODUCT_VIDEO, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment('Product video table');

            $table->bigIncrements('product_video_id')->comment('Primary key');
            $table->uuid('uuid')->unique()->comment('Unique universal identifier');
            $table->unsignedBigInteger('product_id')->comment('Related product ID');
            $table->string('video_url')->comment('Video URL');
            $table->string('title')->nullable()->comment('Video title');
            $table->unsignedInteger('sort_order')->default(0)->comment('Sort order');
            $table->timestamp('created_at')->useCurrent()->comment('Creation timestamp');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Created by user ID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('Updated by user ID');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->comment('Update timestamp');

            $table->index(['product_id', 'sort_order'], 'idx_product_video');
        });

        Schema::create(self::TABLE_CAT_PRODUCT_TRANSLATION, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment('Product translation table');

            $table->bigIncrements('product_translation_id')->comment('Primary key');
            $table->uuid('uuid')->unique()->comment('Unique universal identifier');
            $table->unsignedBigInteger('product_id')->comment('Related product ID');
            $table->string('language_code', 5)->comment('Language code (ISO 639-1)');
            $table->string('name')->comment('Translated product name');
            $table->text('description')->nullable()->comment('Translated product description');
            $table->string('meta_title')->nullable()->comment('SEO meta title');
            $table->text('meta_description')->nullable()->comment('SEO meta description');
            $table->string('meta_keywords')->nullable()->comment('SEO meta keywords');
            $table->string('unique_key')->virtualAs("CONCAT(product_id, '-', language_code)")->unique()->comment('Unique combination of product and language');
            $table->timestamp('created_at')->useCurrent()->comment('Creation timestamp');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Created by user ID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('Updated by user ID');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->comment('Update timestamp');

            $table->index(['language_code'], 'idx_product_translation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE_CAT_PRODUCT_TRANSLATION);
        Schema::dropIfExists(self::TABLE_CAT_PRODUCT_VIDEO);
        Schema::dropIfExists(self::TABLE_CAT_PRODUCT_IMAGE);
        Schema::dropIfExists(self::TABLE_CAT_PRODUCT);
        Schema::dropIfExists(self::TABLE_DEF_CAT_CATEGORY);
        Schema::dropIfExists(self::TABLE_DEF_CAT_LANGUAGE);
    }
};
