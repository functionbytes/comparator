<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_reference', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reference')->nullable();
            $table->integer('default_minderest')->default(0);
            $table->unsignedBigInteger('combination_id')->nullable();
            $table->unsignedBigInteger('attribute_id')->nullable();
            $table->integer('stock')->default(0);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->text('tags')->nullable();
            $table->unsignedBigInteger('id_articulo')->nullable();
            $table->integer('unidades_oferta')->nullable();
            $table->string('estado_gestion', 100)->nullable();
            $table->boolean('es_segunda_mano')->default(0);
            $table->boolean('externo_disponibilidad')->default(0);
            $table->string('codigo_proveedor', 191)->nullable();
            $table->decimal('precio_costo_proveedor', 12, 2)->nullable();
            $table->decimal('tarifa_proveedor', 12, 2)->nullable();
            $table->boolean('es_arma')->default(0);
            $table->boolean('es_arma_fogueo')->default(0);
            $table->boolean('es_cartucho')->default(0);
            $table->string('ean')->nullable();
            $table->string('upc')->nullable();

            $table->softDeletes();
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['reference', 'product_id'], 'product_reference_lang_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reference');
    }
};
