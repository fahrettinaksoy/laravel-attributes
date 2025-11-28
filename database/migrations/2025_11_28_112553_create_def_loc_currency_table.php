<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('def_loc_currency', function (Blueprint $table) {
            $table->bigIncrements('currency_id');
            $table->string('uuid')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('symbol_left')->nullable();
            $table->string('symbol_right')->nullable();
            $table->string('decimal_place');
            $table->string('decimal_point');
            $table->string('thousand_point');
            $table->string('value');
            $table->string('source')->nullable();
            $table->string('last_synced_at')->nullable();
            $table->boolean('is_crypto')->nullable()->default(true);
            $table->boolean('status')->nullable()->default(true);
            $table->timestamp('created_at')->nullable()->default('0');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();


        });
    }

    public function down(): void
    {
        Schema::dropIfExists('def_loc_currency');
    }
};