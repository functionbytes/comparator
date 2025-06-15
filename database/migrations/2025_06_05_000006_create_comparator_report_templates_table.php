<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparator_report_templates', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('comparator_id');
            $table->unsignedBigInteger('lang_id');
            $table->unsignedBigInteger('type_id');

            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->integer('total_products_evaluated')->nullable();
            $table->integer('total_categories_evaluated')->nullable();
            $table->integer('total_competitors_found')->nullable();
            $table->string('file_path', 1024)->nullable();
            $table->text('error_message')->nullable();
            $table->json('config')->nullable();
            $table->json('filters')->nullable(); // Filtros adicionales como {"min_price": 10, "max_price": 1000}
            $table->json('export_formats')->nullable(); // ["xlsx", "csv", "pdf"]
            $table->integer('retention_days')->default(30); // Días para mantener los reportes
            $table->boolean('compare_with_previous')->default(false); // Comparar con reporte anterior
            $table->integer('max_products')->nullable(); // Límite de productos a analizar
            $table->boolean('include_out_of_stock')->default(false);
            $table->tinyInteger('available')->default(0);

            $table->foreign('comparator_id')->references('id')->on('comparators')->onDelete('cascade');
            $table->foreign('lang_id')->references('id')->on('langs')->onDelete('cascade');
            $table->foreign('type_id')->references('id')->on('comparator_reports_types')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_report_templates');
    }
};
