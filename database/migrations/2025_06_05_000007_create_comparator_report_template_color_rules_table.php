<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparator_report_templates_color_rules', function (Blueprint $table) {
            $table->foreignId('template_id')->constrained('comparator_report_templates')->onDelete('cascade');
            $table->string('name');
            $table->enum('apply_to', ['cell', 'row', 'column']);
            $table->enum('condition_field', ['price_difference', 'margin_percentage', 'competitor_price', 'our_price']);
            $table->enum('condition_operator', ['>', '<', '>=', '<=', '=', '!=', 'between']);
            $table->decimal('condition_value1', 10, 2);
            $table->decimal('condition_value2', 10, 2)->nullable(); // Para 'between'
            $table->string('background_color')->nullable();
            $table->string('text_color')->nullable();
            $table->boolean('is_bold')->default(false);
            $table->integer('priority')->default(0);
            $table->tinyInteger('available')->default(0);
            $table->timestamps();


            $table->unique(['template_id', 'priority'], 'template_templates_color_rules_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_report_templates_color_rules');
    }
};
