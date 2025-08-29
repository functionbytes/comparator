<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uid', 36)->unique();
            $table->string('title');
            $table->string('iso_code', 5);                      // 👈 NUEVO
            $table->tinyInteger('available')->default(0);
            $table->timestamps();

            $table->unique(['title', 'iso_code']);              // 👈 NUEVO (evita duplicados por país)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitors'); // 👈 corregido (antes decía 'sellers')
    }
};
