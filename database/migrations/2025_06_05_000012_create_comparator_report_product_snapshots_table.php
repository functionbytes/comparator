<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparator_report_product_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('product_id')->constrained('comparator_products')->onDelete('cascade');
            $table->foreignId('competitor_id')->constrained('competitors')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('sellers')->onDelete('cascade');
            $table->decimal('shipping', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            $table->integer('quantity')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('price_no_shipping', 10, 2)->nullable();
            $table->boolean('is_marketplace')->default(false);
            $table->string('marketplace_name')->nullable();
            $table->enum('shipping_type', ['included', 'separate', 'free'])->default('separate');
            $table->string('url', 2048)->nullable();
            $table->foreignId('report_id')->constrained('comparator_reports')->onDelete('cascade');
            $table->dateTime('captured_at');
            $table->timestamp('created_at')->useCurrent();

            // Índices con nombres personalizados
            $table->index(['product_id', 'captured_at'], 'idx_prod_captured');
            $table->index(['competitor_id', 'captured_at'], 'idx_competitor_captured');
            $table->index(['product_id', 'competitor_id', 'seller_id', 'captured_at'], 'prod_comp_sell_capt_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_report_product_snapshots');
    }
};
