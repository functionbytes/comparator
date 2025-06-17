<?php

namespace App\Models\Prestashop\Category;

use Illuminate\Database\Eloquent\Model;

class CategoryLang extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_category_lang'; // Usamos tu prefijo
    protected $primaryKey = null; // Clave primaria compuesta
    public $incrementing = false;

    public $timestamps = false;
}
