<?php

namespace App\Models\Prestashop\Shop;

use Illuminate\Database\Eloquent\Model;

class ShopGroup extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_shop_group';
    protected $primaryKey = 'id_shop_group';
    public $timestamps = false;

    protected $fillable = [
        'id_shop_group', 'name', 'active', 'deleted', 'date_add', 'date_upd'
    ];
    public function shops()
    {
        return $this->hasMany('App\Models\Prestashop\Shop\Shop', 'id_shop_group', 'id_shop_group');
    }
}
