<?php

namespace App\Models\Prestashop;

use Illuminate\Database\Eloquent\Model;
class Lang extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_lang';
    protected $primaryKey = 'id_lang';
    public $timestamps = false;

    protected $fillable = [
        'id_lang', 'name', 'iso_code', 'active', 'date_format_lite', 'date_format_full'
    ];
    public function carts()
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_lang', 'id_lang');
    }
}
