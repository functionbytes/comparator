<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLang extends Model
{
    protected $table = 'product_lang';

    protected $fillable = [
        'product_id',
        'prestashop_id',
        'lang_id',
        'title',
        'characteristics',
        'url',
        'price',
        'stock',
        'from_quantity',
        'reduction',
        'reduction_tax',
        'reduction_type',
        'from',
        'to',
        'comparator',
        'label',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'from_quantity' => 'integer',
        'reduction' => 'decimal:2',
        'reduction_tax' => 'boolean',
        'from' => 'datetime',
        'to' => 'datetime',
        'comparator' => 'integer',
        'label' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Product', 'product_id');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo('App\Models\Lang', 'lang_id');
    }

}
