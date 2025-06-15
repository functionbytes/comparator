<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparator_price_thresholds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('template_id')->constrained('comparator_report_templates')->onDelete('cascade');
            $table->enum('type', ['percentage', 'fixed_amount']);
            $table->decimal('min_threshold', 10, 2)->nullable();
            $table->decimal('max_threshold', 10, 2)->nullable();
            $table->enum('action', ['alert', 'highlight', 'exclude']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_price_thresholds');
    }
};
