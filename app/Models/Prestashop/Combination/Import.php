<?php

namespace App\Models\Prestashop\Combination;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
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

    public function scopeManagement($query)
    {
        return $query->where('estado_gestion', '!=', 0)->where('es_segunda_mano', '!=', 1);
    }

    public function productAttribute()
    {
        return $this->hasOne('App\Models\Prestashop\Product\ProductAttribute', 'id_product_attribute', 'id_product_attribute')->select('id_product','reference','id_product_attribute');
    }

    public function getBaseProduct(): ?\App\Models\Prestashop\Product\Product
    {
        return $this->id_product_attribute
            ? $this->productAttribute?->product
            : $this->product;
    }

    public function getBaseProductId(): ?int
    {
        return $this->id_product_attribute
            ? $this->productAttribute?->id_product
            : $this->id_product;
    }


}
