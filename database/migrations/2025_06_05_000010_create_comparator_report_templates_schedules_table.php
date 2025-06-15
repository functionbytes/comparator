<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparator_report_templates_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('template_id')->constrained('comparator_report_templates')->onDelete('cascade');
            $table->enum('frequency', ['daily', 'twice_daily', 'weekly', 'monthly', 'manual']);
            $table->json('time_slots')->nullable()->comment('Ej: ["08:00", "20:00"]');
            $table->integer('day_of_week')->nullable()->comment('0-6 para weekly');
            $table->integer('day_of_month')->nullable()->comment('1-31 para monthly');
            $table->tinyInteger('available')->default(0);
            $table->dateTime('last_run_at')->nullable();
            $table->dateTime('next_run_at')->nullable()->index();
            $table->timestamps();

            $table->index(['available', 'next_run_at'], 'comparator_report_templates_schedules');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_report_templates_schedules');
    }
};
