<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparator_report_analytics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('report_id')->unique()->constrained('comparator_reports')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->integer('total_products_analyzed')->nullable();
            $table->integer('new_products_found')->nullable();
            $table->integer('products_with_price_changes')->nullable();
            $table->decimal('avg_price_variation', 8, 2)->nullable();
            $table->integer('products_below_threshold')->nullable();
            $table->integer('products_above_threshold')->nullable();
            $table->integer('processing_time_seconds')->nullable()->comment('Segundos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_report_analytics');
    }
};
