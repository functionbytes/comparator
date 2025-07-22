<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReferenceManagement extends Model
{
    protected $table = 'product_reference_management';

    protected $fillable = [
        'product_reference_id',
        'tags',
        'id_articulo',
        'unidades_oferta',
        'estado_gestion',
        'es_segunda_mano',
        'externo_disponibilidad',
        'codigo_proveedor',
        'precio_costo_proveedor',
        'tarifa_proveedor',
        'es_arma',
        'es_arma_fogueo',
        'es_cartucho',
        'ean',
        'upc',
    ];

    public function reference()
    {
        return $this->belongsTo(ProductReference::class, 'product_reference_id');
    }
}
