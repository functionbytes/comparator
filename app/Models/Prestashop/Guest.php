<?php

namespace App\Models\Prestashop;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_guest';
    protected $primaryKey = 'id_guest';
    public $timestamps = false;

    protected $fillable = [
        'id_guest', 'id_customer', 'id_operating_system', 'id_web_browser', 'accept_language', 'screen_resolution', 'ip_address', 'date_add'
    ];


    public function customer()
    {
        return $this->belongsTo('App\Models\Prestashop\Customer', 'id_customer', 'id_customer');
    }

    public function carts()
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_guest', 'id_guest');
    }
}


