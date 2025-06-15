<?php

namespace App\Models\Prestashop\Banner;

use Illuminate\Database\Eloquent\Model;

class BannerLang extends Model
{

    protected $connection = 'prestashop';
    protected $table = "aalv_banner_lang";
    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_lang',
        'banner_id',
        'banner',
        'banner_mobile',
        'link',
        'name',
        'deporte',
    ];

    public function banner()
    {
        return $this->belongsTo('App\Models\Prestashop\Banner\Banner', 'banner_id', 'id');
    }

    public function lang()
    {
        return $this->belongsTo('App\Models\Lang' ,'lang_id', 'id');
    }



}