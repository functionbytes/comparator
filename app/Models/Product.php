<?php

namespace App\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{

    use HasUid;

    protected $table = 'products';

    protected $fillable = [
        'uid',
        'ean',
        'upc',
        'manufacturer_id',
        'provider_id',
        'prestashop_id',
        'article_id',
        'category_id',
        'type',
        'available',
        'created_at',
        'updated_at',
    ];

    public function references(): HasMany
    {
        return $this->hasMany('App\Models\ProductReference', 'product_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo('App\Models\Category', 'category_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id');
    }


    public function langss(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Lang', 'product_lang', 'product_id', 'lang_id')
            ->withPivot('title', 'characteristics', 'url', 'price', 'stock', 'comparator', 'label')
            ->withTimestamps();
    }

    public function langs()
    {
        return $this->belongsToMany(
            'App\Models\Lang',
            'product_lang',
            'product_id',
            'lang_id'
        )
            ->withPivot([
                'title',
                'characteristics',
                'price',
                'stock',
                'reduction',
                'comparator',
            ])
            ->withTimestamps();
    }



}
