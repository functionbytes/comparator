<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('comparator_report_templates_categories_langs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('template_id')->constrained('comparator_report_templates')->onDelete('cascade');
            $table->foreignId('lang_id')->constrained('langs')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['template_id', 'lang_id'], 'report_templates_categories_langs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_report_templates_categories_langs');
    }

};
