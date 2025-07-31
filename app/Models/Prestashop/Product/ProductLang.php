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
        'id_shop',
        'id_lang',
        'name',
        'description',
        'description_short',
        'link_rewrite',
    ];

    public function getUrlAttribute()
    {

        $base = rtrim(env('URL_PRESTASHOP', 'https://a-alvarez.com'), '/');
        $lang = $this->relationLoaded('lang') ? $this->lang : $this->lang()->first();

        $iso = optional($lang)->iso_code ?? 'es';
        $langSegment = ($iso !== 'es') ? "/$iso" : '';
        $url = "{$base}{$langSegment}/{$this->id_product}-{$this->link_rewrite}";
        return $url;
    }

    public function lang()
    {
        return $this->hasOne('App\Models\Prestashop\Lang', 'id_lang', 'id_lang');
    }

}
