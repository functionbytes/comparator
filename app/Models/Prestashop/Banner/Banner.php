<?php

namespace App\Models\Prestashop\Banner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{

    protected $connection = 'prestashop';
    protected $table = "aalv_banners";
    public $timestamps = false;

    protected $fillable = [
        'id',
        'active',
        'date_start',
        'date_end',
        'type',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query, $uid)
{
        return $query->where('uid', $uid)->first();
}

    public function scopeAvailable($query)
    {
        return $query->where('active', 1);
    }

    public function langs(): HasMany
    {
        return $this->hasMany(BannerLang::class, 'banner_id', 'id');
    }

    public function scopeLang($query, $lang)
    {
        return $query->whereHas('langs', function ($q) use ($lang) {
            $q->where('id_lang', $lang);
        });
    }



}
