<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparator_report_templates_schedules_execution_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('report_id');

            // Foreign key con nombre corto
            $table->foreign('report_id', 'fk_crtsel_report')
                ->references('id')->on('comparator_reports')
                ->onDelete('cascade');

            $table->enum('level', ['info', 'warning', 'error', 'debug']);
            $table->string('message');
            $table->json('context')->nullable();
            $table->string('source')->nullable();
            $table->timestamp('logged_at')->useCurrent();

            // Índices con nombres cortos
            $table->index(['report_id', 'level'], 'idx_report_level');
            $table->index('logged_at', 'idx_logged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_report_templates_schedules_execution_logs');
    }
};
