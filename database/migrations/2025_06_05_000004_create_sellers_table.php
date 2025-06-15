<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uid', 36)->unique();
            $table->unsignedBigInteger('competitor_id');
            $table->string('title');
            $table->timestamps();
            $table->foreign('competitor_id')->references('id')->on('competitors')->onDelete('cascade');
            $table->unique(['competitor_id', 'title']);
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
