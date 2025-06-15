<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparator_report_notifications', function (Blueprint $table) {
            $table->foreignId('template_id')->constrained('comparator_report_templates')->onDelete('cascade');
            $table->enum('channel', ['email', 'slack', 'webhook']);
            $table->json('recipients'); // emails, webhook URLs, etc
            $table->json('conditions')->nullable(); // Ej: {"price_drop": 10, "new_competitors": 5}
            $table->enum('trigger', ['always', 'on_change', 'on_condition']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_report_notifications');
    }
};
