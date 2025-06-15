<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparator_reports', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->unsignedBigInteger('comparator_id');
            $table->unsignedBigInteger('lang_id');
            $table->unsignedBigInteger('type_id');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->integer('total_products_evaluated')->nullable();
            $table->integer('total_competitors_found')->nullable();
            $table->string('file_path', 1024)->nullable();
            $table->text('error_message')->nullable();
            $table->json('summary')->nullable(); // Resumen ejecutivo del reporte
            $table->integer('priority')->default(0); // Para ordenar ejecución

            $table->json('price_suggestions')->nullable(); // Almacenar sugerencias generadas
            $table->json('margin_analysis')->nullable(); // Análisis de márgenes
            $table->decimal('avg_competitor_price', 10, 2)->nullable();
            $table->decimal('min_competitor_price', 10, 2)->nullable();
            $table->decimal('max_competitor_price', 10, 2)->nullable();


            $table->timestamps();

            $table->foreign('comparator_id')->references('id')->on('comparators')->onDelete('cascade');
            $table->foreign('lang_id')->references('id')->on('langs')->onDelete('cascade');
            $table->foreign('type_id')->references('id')->on('comparator_reports_types')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('comparator_report_templates')->onDelete('set null');
            $table->foreign('schedule_id')->references('id')->on('comparator_report_templates_schedules')->onDelete('set null');

            $table->index('status');
            $table->index('scheduled_at');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_reports');
    }
};
