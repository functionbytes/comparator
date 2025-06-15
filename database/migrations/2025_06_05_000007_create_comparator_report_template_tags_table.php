<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('comparator_report_templates_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('template_id')->constrained('comparator_report_templates')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('product_tags')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['template_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_report_templates_tags');
    }
};
