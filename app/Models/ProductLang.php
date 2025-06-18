<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLang extends Model
{
    use SoftDeletes;

    protected $table = 'product_lang';

    protected $fillable = [
        'product_id',
        'lang_id',
        'title',
        'characteristics',
        'price',
        'url',
        'stock',
        'comparator',
        'reduction',
        'available',
    ];

    protected $casts = [
        'product_id'     => 'integer',
        'lang_id'        => 'integer',
        'title'          => 'string',
        'characteristics'=> 'string',
        'url'=> 'string',
        'price'          => 'decimal:2',
        'stock'          => 'integer',
        'comparator'     => 'boolean',
        'reduction'      => 'decimal:6',
        'available'      => 'boolean',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'deleted_at'     => 'datetime',
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
