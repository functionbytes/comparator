<?php

namespace App\Models\Prestashop\Category;

use App\Models\Prestashop\Product\Product;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_category'; // Usamos tu prefijo
    protected $primaryKey = 'id_category';

    public $timestamps = false;

    /**
     * Relación para obtener el nombre y otros datos del idioma.
     */
    public function lang()
    {
        // Asumimos id_lang = 1 como en tu modelo de Product
        // Puedes hacerlo dinámico si lo necesitas: ->where('id_lang', session('lang_id', 1))
        return $this->hasOne(CategoryLang::class, 'id_category', 'id_category')->where('id_lang', 1);
    }

    /**
     * Accesor para obtener el nombre directamente.
     * Uso: $category->name
     */
    public function getNameAttribute()
    {
        return $this->lang->name ?? null;
    }

    /**
     * Relación para obtener el padre directo de esta categoría.
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'id_parent', 'id_category');
    }

    /**
     * Relación para obtener los hijos directos de esta categoría.
     */
    public function children()
    {
        return $this->hasMany(self::class, 'id_parent', 'id_category');
    }

    /**
     * Productos que tienen esta categoría como predeterminada.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'id_category_default', 'id_category');
    }
}
