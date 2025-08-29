<?php

namespace App\Models\Prestashop\Combination;

use Illuminate\Database\Eloquent\Model;

class Unique extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_combinacionunica_import';
    protected $primaryKey = 'id_product';
    public $timestamps = false;

    protected $fillable = [
        'id_product',
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
        'grupo'
    ];

    public function scopeByProductIds($query, array $ids)
    {
        return $query->whereIn('id_product', $ids);
    }

    public function scopeManagement($query)
    {
        return $query->where('estado_gestion', '!=', 0)->where('es_segunda_mano', '!=', 1);
    }

    public function product()
    {
        return $this->hasOne('App\Models\Prestashop\Product\Product', 'id_product', 'id_product')->select('id_product', 'reference', 'id_category_default');
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
