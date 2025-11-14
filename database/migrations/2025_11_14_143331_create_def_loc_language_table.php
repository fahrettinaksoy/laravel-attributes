<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('def_loc_language', function (Blueprint $table) {
            $table->bigIncrements('language_id');
            $table->string('uuid')->unique();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->string('description');
            $table->string('flag_path');
            $table->string('direction');
            $table->string('directory');
            $table->string('locale');
            $table->integer('sort_order')->default('0');
            $table->boolean('status')->nullable()->default(true);
            $table->timestamp('created_at')->nullable()->default('0');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable()->default('0');


        });
    }

    public function down(): void
    {
        Schema::dropIfExists('def_loc_language');
    }
};