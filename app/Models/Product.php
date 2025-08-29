<?php

namespace App\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{

    use HasUid;

    protected $table = 'products';

    protected $fillable = [
        'uid',
        'manufacturer_id',
        'provider_id',
        'prestashop_id',
        'id_modelo',
        'article_id',
        'category_id',
        'type',
        'stock',
        'available',
        'created_at',
        'updated_at',
    ];

    public function references(): HasMany
    {
        return $this->hasMany('App\Models\ProductReference', 'product_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo('App\Models\Category', 'category_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id');
    }


    public function langss(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Lang', 'product_lang', 'product_id', 'lang_id')
            ->withPivot('title', 'url', 'stock', 'comparator', 'label')
            ->withTimestamps();
    }

    public function langs()
    {
        return $this->belongsToMany(
            \App\Models\Lang::class,
            'product_lang',     // Tabla pivote
            'product_id',       // Clave foránea en la tabla pivote hacia este modelo
            'lang_id'           // Clave foránea hacia el modelo Lang
        )
            ->withPivot([
                'title',
                'url',
                'img',
            ])
            ->withTimestamps();
    }

    // Útil para otras consultas
    public function lang(): HasMany
    {
        return $this->hasMany(ProductLang::class, 'product_id', 'id');
    }

    public function defaultLang()
    {
        return $this->hasOne(ProductLang::class, 'product_id', 'id')->where('product_lang.lang_id', 1);
    }

    public function getLangData($langId)
    {
        return $this->langs->firstWhere('id', $langId);
    }

    public function manufacturer(): BelongsTo
    {
        // manufacturer_id vive en la propia tabla products
        // Como sigue la convención, NO hace falta indicar claves:
        return $this->belongsTo(Manufacturer::class);
    }

    public function importtt(): HasOne
    {
        return $this->hasOne('App\Models\Prestashop\Product\ProductImport', 'id_product', 'prestashop_id');
    }
}
