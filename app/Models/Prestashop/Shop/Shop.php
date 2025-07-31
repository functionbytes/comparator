<?php

namespace App\Models\Prestashop\Shop;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_shop';
    protected $primaryKey = 'id_shop';
    public $timestamps = false;

    protected $fillable = [
        'id_shop', 'id_shop_group', 'name', 'active', 'date_add', 'date_upd'
    ];
    public function group()
    {
        return $this->belongsTo('App\Models\Prestashop\Shop\ShopGroup', 'id_shop_group', 'id_shop_group');
    }

    public function carriers()
    {
        return $this->hasMany('App\Models\Prestashop\Carrier', 'id_shop', 'id_shop');
    }

    public function carts()
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_shop', 'id_shop');
    }
}
