<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_reference_management')) {
            Schema::create('product_reference_management', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('product_reference_id');
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

                $table->timestamps();

                $table->foreign('product_reference_id')
                    ->references('id')->on('product_reference')
                    ->onDelete('cascade');

                $table->unique('product_reference_id', 'uniq_prm_reference');
            });
        } else {
            Schema::table('product_reference_management', function (Blueprint $table) {
                if (Schema::hasColumn('product_reference_management', 'lang_id')) {
                    $table->dropForeign(['lang_id']);
                    $table->dropColumn('lang_id');
                }
                // agregar columnas nuevas (mismas que arriba) chequeando si existen si migras varias veces
                if (!Schema::hasColumn('product_reference_management', 'id_articulo')) {
                    $table->unsignedBigInteger('id_articulo')->nullable()->after('product_reference_id');
                    // resto...
                }
            });
        }
    }


    public function down(): void
    {
         Schema::table('product_reference_management', function (Blueprint $table) {
            // revertir campos
            $table->dropColumn([
                'id_articulo', 'unidades_oferta', 'estado_gestion', 'es_segunda_mano',
                'externo_disponibilidad', 'codigo_proveedor', 'precio_costo_proveedor',
                'tarifa_proveedor', 'es_arma', 'es_arma_fogueo', 'es_cartucho'
            ]);

            // volver a crear lang_id si quieres revertir
            $table->unsignedBigInteger('lang_id')->nullable();
            $table->foreign('lang_id')->references('id')->on('langs')->onDelete('cascade');
        });
    }
};