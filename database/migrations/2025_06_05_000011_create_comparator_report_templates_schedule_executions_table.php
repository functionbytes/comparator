<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparator_report_templates_schedules_executions', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Declaración manual
            $table->unsignedBigInteger('schedule_id');
            $table->foreign('schedule_id', 'fk_crtse_schedule')
                ->references('id')
                ->on('comparator_report_templates_schedules')
                ->onDelete('cascade');

            $table->dateTime('executed_at');
            $table->dateTime('next_execution_at')->nullable();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->integer('reports_generated')->default(0);
            $table->text('error_log')->nullable();
            $table->timestamps();

            $table->index(['schedule_id', 'executed_at'], 'idx_schedule_executed');
            $table->index('next_execution_at', 'idx_next_exec');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_report_templates_schedules_executions');
    }
};
