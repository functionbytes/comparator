<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comparators_lang', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('comparator_id');
            $table->unsignedBigInteger('lang_id');
            $table->string('key')->nullable();
            $table->timestamps();
            $table->foreign('comparator_id')->references('id')->on('comparators')->onDelete('cascade');
            $table->foreign('lang_id')->references('id')->on('langs')->onDelete('cascade');
            $table->unique(['comparator_id', 'lang_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comparators_lang');
    }
};
