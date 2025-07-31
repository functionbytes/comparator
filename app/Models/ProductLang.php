<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLang extends Model
{
    use SoftDeletes;

    protected $table = "product_lang";
    protected $primaryKey = 'product_id';
    public $incrementing = false; // si no es autoincremental
    public $timestamps = true; // si usas created_at y updated_at

    protected $fillable = [
        'product_id',
        'lang_id',
        'title',
        'url',
        'img',
        'comparator',
        'available',
        'stock',
    ];

    protected $casts = [
        'product_id'     => 'integer',
        'lang_id'        => 'integer',
        'title'          => 'string',
        'url'            => 'string',
        'img'            => 'string',
        'comparator'     => 'boolean',
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
