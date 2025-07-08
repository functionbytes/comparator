<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturer_lang', callback: function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('manufacturer_id');
            $table->unsignedBigInteger('lang_id');
            $table->string('title')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('manufacturer_id')->references('id') ->on('manufacturers')->onDelete('cascade');
            $table->foreign('lang_id')->references('id')->on('langs')->onDelete('cascade');
            $table->unique(['manufacturer_id', 'lang_id','id'], 'manufacturer_lang_unique');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturer_lang');
    }
};
