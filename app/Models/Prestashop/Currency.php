<?php

namespace App\Models\Prestashop;

use Illuminate\Database\Eloquent\Model;
class Currency extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_currency';
    protected $primaryKey = 'id_currency';
    public $timestamps = false;

    protected $fillable = [
        'id_currency', 'name', 'iso_code', 'conversion_rate', 'active'
    ];

    public function carts()
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_currency', 'id_currency');
    }
}
