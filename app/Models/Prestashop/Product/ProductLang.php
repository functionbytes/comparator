<?php

namespace App\Models\Prestashop\Product;

use Illuminate\Database\Eloquent\Model;

class ProductLang extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_product_lang';

    public $timestamps = false;

    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'id_product',
        'id_lang',
        'name',
        'description',
        'description_short',
        'link_rewrite',
    ];
}
