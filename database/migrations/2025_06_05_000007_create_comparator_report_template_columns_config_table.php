<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparator_report_templates_columns_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('template_id')->constrained('comparator_report_templates')->onDelete('cascade');
            $table->string('column_key'); // 'reference', 'matches', 'competitor_X', etc.
            $table->string('column_label');
            $table->integer('display_order');
            $table->enum('column_type', ['product_info', 'price_analysis', 'competitor', 'marketplace', 'suggestion']);
            $table->tinyInteger('available')->default(0);
            $table->json('formatting_rules')->nullable(); // Reglas de formato/color
            $table->timestamps();


            $table->index(['template_id', 'display_order'], 'template_templates_columns_config_index');
            $table->unique(['template_id', 'column_key'], 'template_templates_columns_config_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_report_templates_columns_config');
    }
};
