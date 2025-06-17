<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_label', callback: function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('label_id');
            $table->timestamps();
            $table->foreign('product_id')->references('id') ->on('products')->onDelete('cascade');
            $table->foreign('label_id')->references('id') ->on('labels')->onDelete('cascade');
            $table->unique(['product_id','label_id'], 'product_label_unique');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_label');
    }
};
