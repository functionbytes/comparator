<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('comparator_report_templates_categories_providers', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('provider_id');

            $table->foreign('template_id', 'fk_crtcp_template')
                ->references('id')->on('comparator_report_templates')
                ->onDelete('cascade');

            $table->foreign('provider_id', 'fk_crtcp_provider')
                ->references('id')->on('providers')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['template_id', 'provider_id'], 'providers_unique');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_report_templates_categories_providers');
    }

};
