<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparator_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uid', 36)->unique();
            $table->string('reference')->unique()->index();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->foreignId('provider_id')->nullable()->constrained('providers')->onDelete('set null');

            // Precios y costos
            $table->decimal('current_price', 10, 2)->nullable();
            $table->decimal('current_price_no_tax', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('new_cost_price', 10, 2)->nullable();

            // Márgenes calculados
            $table->decimal('margin_amount', 10, 2)->nullable();
            $table->decimal('margin_percentage', 5, 2)->nullable();
            $table->decimal('new_margin_amount', 10, 2)->nullable();
            $table->decimal('new_margin_percentage', 5, 2)->nullable();

            // Estados y configuración
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->boolean('fixed_price')->default(false);
            $table->boolean('has_tag')->default(false);
            $table->boolean('visible_web_with_shipping')->default(true);
            $table->boolean('is_external')->default(false);

            // Contadores
            $table->integer('match_count')->default(0);

            $table->timestamps();

            $table->index('status');
            $table->index(['reference', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparator_products');
    }
};
