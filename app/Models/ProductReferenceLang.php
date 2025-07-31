<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductReferenceLang extends Model
{
    protected $table = 'product_reference_lang';

    protected $fillable = [
        'reference_id',
        'lang_id',
        'price',
        'reduction',
        'characteristics',
        'available',
        'url',
    ];

    protected $casts = [
        'reference_id'    => 'integer',
        'characteristics' => 'string',
        'price'           => 'decimal:2',
        'reduction'       => 'decimal:6',
        'url'             => 'string',
        'available'       => 'boolean',
        'deleted_at'      => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function setUrlAttribute($value)
    {
        if (!$value) {

            $productLang = ProductLang::where([
                'product_id' => $this->product->id,
                'lang_id' => $this->lang->id,
            ])->first();

            if ($productLang) {

                $this->attributes['url'] = $productLang->url;

                if (!empty($this->attribute_id)) {
                    $this->attributes['url'] .= '?id_product_attribute=' . $this->attribute_id;
                }
                return;
            }
        }

        $this->attributes['url'] = $value;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Product', 'product_id');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo('App\Models\Lang', 'lang_id');
    }

    public function productLang()
    {
        return $this->hasOne('App\Models\ProductLang', 'product_id', 'product_id')
            ->whereColumn('lang_id', 'lang_id');
    }

    public function reference()
    {
        return $this->belongsTo(ProductReference::class, 'reference_id');
    }

    // public function management() // si solo uno
    // {
    //     return $this->hasOne(LangManagement::class, 'product_reference_id');
    // }

}
