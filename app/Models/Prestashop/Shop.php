<?php

namespace App\Models\Prestashop\Core;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_shop';
    protected $primaryKey = 'id_shop';
    public $timestamps = false;

    protected $fillable = [
        'id_shop_group',
        'name',
        'active',
        'date_add',
        'date_upd',
    ];

    public function products()
    {
        return $this->belongsToMany('App\Models\Prestashop\Product', 'aalv_product_shop', 'id_shop', 'id_product');
    }
}
