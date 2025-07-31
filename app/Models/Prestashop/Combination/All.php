<?php

namespace App\Models\Prestashop\Combination;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class All extends Model
{

    protected $connection = 'prestashop';
    protected $table = "aalv_combinaciones_import";
    protected $primaryKey = 'id_product_attribute';
    public $timestamps = false;

    protected $fillable = [
        'id_product_attribute',
        'id_origen',
        'id_articulo',
        'unidades_oferta',
        'etiqueta',
        'estado_gestion',
        'activo',
        'es_segunda_mano',
        'externo_disponibilidad',
        'codigo_proveedor',
        'precio_costo_proveedor',
        'tarifa_proveedor',
        'es_arma',
        'es_arma_fogueo',
        'es_cartucho',
        'categoria',
        'familia',
        'subfamilia',
        'grupo',
        'created_at',
        'updated_at'
    ];

    public function scopeByProductIds($query, $ids)
    {
        return $query->whereIn('id_product_attribute', $ids);
    }

    public function scopeAvailable($query)
    {
        return $query->where('activo', 1);
    }

    public function scopeManagement($query)
    {
        return $query->where('estado_gestion', '!=', 0);
    }


}
