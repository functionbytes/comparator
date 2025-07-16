<?php

namespace App\Models\Prestashop;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_image';
    protected $primaryKey = 'id_image';
    public $timestamps = false;

    protected $fillable = [
        'id_image', 'id_product', 'position', 'cover', 'sc_path'
    ];

    public function products()
    {
        return $this->hasMany('App\Models\ProductLang', 'product_id', 'id_product');
    }

}
