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
            Schema::create('product_reference', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reference')->nullable();
            $table->string('url', 2048)->nullable();
            $table->unsignedBigInteger('lang_id')->nullable();
            $table->unsignedBigInteger('combination_id')->nullable();
            $table->unsignedBigInteger('attribute_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->tinyInteger('available')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('lang_id')->references('id')->on('langs')->onDelete('cascade');
            $table->unique(['reference','id','attribute_id', 'product_id', 'lang_id'], 'product_reference_lang_unique');

            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reference');
    }
};
