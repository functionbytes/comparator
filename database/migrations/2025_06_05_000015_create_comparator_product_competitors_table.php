<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparator_product_competitors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('product_id')->constrained('comparator_products')->onDelete('cascade');
            $table->foreignId('competitor_id')->constrained('competitors')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('sellers')->onDelete('cascade');
            $table->decimal('last_price', 10, 2)->nullable();
            $table->decimal('last_shipping', 10, 2)->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->unsignedInteger('times_seen')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'competitor_id', 'seller_id'], 'prod_comp_sell_unique');
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_product_competitors');
    }
};
