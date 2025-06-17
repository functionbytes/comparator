<?php

namespace App\Models\Prestashop\Banner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Combination extends Model
{

    protected $connection = 'prestashop';
    protected $table = "aalv_combinaciones_import";
    public $timestamps = false;

    protected $fillable = [
        'id',
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

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query, $uid)
{
        return $query->where('uid', $uid)->first();
}

    public function scopeAvailable($query)
    {
        return $query->where('active', 1);
    }

    public function langs(): HasMany
    {
        return $this->hasMany(BannerLang::class, 'banner_id', 'id');
    }

    public function scopeLang($query, $lang)
    {
        return $query->whereHas('langs', function ($q) use ($lang) {
            $q->where('id_lang', $lang);
        });
    }



}
