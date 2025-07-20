<?php

namespace App\Models\Prestashop\Combination;

use Illuminate\Database\Eloquent\Model;

class Unique extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_combinacionunica_import';
    protected $primaryKey = 'id_lang'; // <-- Este podría no ser correcto si no es una clave única
    public $timestamps = false;

    protected $fillable = [
        'id_product', 'id_origen', 'id_articulo', 'unidades_oferta', 'etiqueta',
        'estado_gestion', 'activo', 'es_segunda_mano', 'externo_disponibilidad',
        'codigo_proveedor', 'precio_costo_proveedor', 'tarifa_proveedor',
        'es_arma', 'es_arma_fogueo', 'es_cartucho', 'categoria', 'familia',
        'subfamilia', 'grupo'
    ];

    public function scopeByProductIds($query, array $ids)
    {
        return $query->whereIn('id_product', $ids);
    }

    public function scopeAvailable($query)
    {
        return $query->where('activo', 1);
    }
}
