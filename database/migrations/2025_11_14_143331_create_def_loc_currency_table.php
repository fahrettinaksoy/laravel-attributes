<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('def_loc_currency', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('currency_id');
            $table->uuid('uuid');
            $table->string('code', 10);
            $table->string('name', 255);
            $table->string('description', 500)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('symbol_left', 50)->nullable();
            $table->string('symbol_right', 50)->nullable();
            $table->string('decimal_place', 10);
            $table->string('decimal_point', 5);
            $table->string('thousand_point', 5);
            $table->string('value', 50);
            $table->string('source', 100)->nullable();
            $table->string('last_synced_at', 50)->nullable();
            $table->boolean('is_crypto')->default(true);
            $table->boolean('status')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique('uuid', 'uq_def_loc_currency_uuid');
            $table->unique('code', 'uq_def_loc_currency_code');
            $table->index('is_crypto', 'idx_def_loc_currency_is_crypto');
            $table->index('status', 'idx_def_loc_currency_status');
            $table->index('created_by', 'idx_def_loc_currency_created_by');
            $table->index('updated_by', 'idx_def_loc_currency_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('def_loc_currency');
    }
};
