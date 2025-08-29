<?php

// database/migrations/2025_08_14_000000_create_price_changes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('price_changes', function (Blueprint $table) {
            $table->id();
            $table->string('referencia', 150)->index();
            $table->decimal('precio_con_iva', 12, 4)->nullable();
            $table->decimal('nuevo_precio_con_iva', 12, 4);
            $table->enum('source', ['manual','sugerencia'])->default('manual');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->json('contexto')->nullable(); // opcional: guarda datos útiles de la fila
            $table->timestamps();

            $table->index(['referencia','created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('price_changes');
    }
};
