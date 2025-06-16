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
            $table->unsignedBigInteger('prestashop_id')->nullable();
            $table->unsignedBigInteger('lang_id');
            $table->string('title')->nullable();
            $table->text('characteristics')->nullable();
            $table->string('url', 2048)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->integer('stock')->nullable();
            $table->tinyInteger('comparator')->default(0);
            $table->tinyInteger('label')->default(0);
            $table->integer('from_quantity')->default(1);
            $table->decimal('reduction', 20, 6)->default(0);
            $table->boolean('reduction_tax')->default(true);
            $table->enum('reduction_type', ['amount', 'percentage'])->default('amount');
            $table->timestamp('from')->nullable();
            $table->timestamp('to')->nullable();
            $table->timestamps();
            $table->foreign('product_id')->references('id') ->on('products')->onDelete('cascade');
            $table->foreign('lang_id')->references('id')->on('langs')->onDelete('cascade');
            $table->unique(['product_id', 'lang_id', 'prestashop_id'], 'product_lang_unique');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_lang');
    }
};
