<?php

namespace App\Models\Prestashop;
use Illuminate\Database\Eloquent\Model;

class Carrier extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_carrier';
    protected $primaryKey = 'id_carrier';
    public $timestamps = false;

    protected $fillable = [
        'id_carrier', 'id_shop', 'name', 'active', 'deleted'
    ];

    public function shop()
    {
        return $this->belongsTo('App\Models\Prestashop\Shop', 'id_shop', 'id_shop');
    }

    public function carts()
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_carrier', 'id_carrier');
    }
}
