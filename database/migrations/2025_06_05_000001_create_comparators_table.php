<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparators', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uid', 36)->unique();
            $table->string('title');
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('api_username')->nullable();
            $table->json('api_config')->nullable();
            $table->tinyInteger('available')->default(0);
            $table->timestamps();
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparators');
    }
};
