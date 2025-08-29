<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\ProductReferenceLang;


class ProductReference extends Model
{
    protected $table = 'product_reference';

    protected $fillable = [
        'reference',
        'default_minderest',
        'combination_id',
        'attribute_id',
        'stock',
        'product_id',
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

    protected $casts = [
        'reference'       => 'string',
        'default_minderest' => 'integer',
        'combination_id'  => 'integer',
        'attribute_id'    => 'integer',
        'stock'          => 'integer',
        'product_id'      => 'integer',
        'deleted_at'      => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'tags' => 'string',
        'id_articulo' => 'string',
        'unidades_oferta' => 'string',
        'estado_gestion' => 'string',
        'es_segunda_mano' => 'string',
        'externo_disponibilidad' => 'string',
        'codigo_proveedor' => 'string',
        'precio_costo_proveedor' => 'string',
        'tarifa_proveedor' => 'string',
        'es_arma' => 'string',
        'es_arma_fogueo' => 'string',
        'es_cartucho' => 'string',
        'ean' => 'string',
        'upc' => 'string',
    ];


    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Product', 'product_id');
    }

    public function langs()
    {
        return $this->hasMany(ProductReferenceLang::class, 'reference_id');
    }

    public function lang($langId)
    {
        return $this->hasOne(ProductReferenceLang::class, 'reference_id')
    ->where('lang_id', $langId)
    ->first();
    }

}
