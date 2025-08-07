<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_lang', callback: function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('lang_id');
            $table->string('title')->nullable();
            $table->integer('stock')->default(0);
            $table->tinyInteger('comparator')->default(0);
            $table->tinyInteger('available')->default(0);
            $table->text('url')->nullable();
            $table->text('img')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('product_id')->references('id') ->on('products')->onDelete('cascade');
            $table->foreign('lang_id')->references('id')->on('langs')->onDelete('cascade');
            $table->unique(['product_id', 'lang_id'], 'product_lang_unique');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_lang');
    }
};
